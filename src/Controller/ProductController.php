<?php

namespace App\Controller;


use App\Entity\Product;
use App\Entity\Variant;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\Cache\Simple\RedisCache;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

class ProductController extends AbstractController
{
    /**
     * @Route("product", methods="GET")
     */
    public function index()
    {
        $productRepository = $this->getDoctrine()->getRepository(Product::class);
        $products = $productRepository->findAll();
        return $this->render('product/index.html.twig', ['products' => $products]);
    }

    /**
     * @Route("/admin/product/create", methods="GET")
     */
    public function create()
    {
        return $this->render('product/create.html.twig');
    }

    /**
     * @Route("/admin/product/store", methods="POST")
     */
    public function store(Request $request, RouterInterface $router)
    {
        $entityManager = $this->getDoctrine()->getManager();

        $product = new Product();
        $product->setTitle($request->get('title'));
        $product->setDescription($request->get('description'));
        $entityManager->persist($product);
        $entityManager->flush();

        $prices = $request->get('prices');
        $colors = $request->get('colors');
        if (is_array($colors))
            foreach ($colors as $key => $color)
            {
                $variant = new Variant();
                $variant->setColor($color);
                $variant->setPrice($prices[$key]);
                $variant->setProductId($product->getId());
                $entityManager->persist($variant);
            }
        $entityManager->flush();

        return new RedirectResponse($router->generate('app_product_index'));
    }

    /**
     * @Route("/product/show/{id}", methods="GET", defaults={"id" = null})
     */
    public function show($id)
    {
        $productRepository = $this->getDoctrine()->getRepository(Product::class);
        $product = $productRepository->find($id);
        return $this->render('product/show.html.twig', ['product' => $product, 'variants' => $product->getVariants()]);
    }

    /**
     * @Route("/admin/product/edit/{id}", methods="GET")
     */
    public function edit($id)
    {
        $productRepository = $this->getDoctrine()->getRepository(Product::class);
        $product = $productRepository->find($id);
        return $this->render('product/edit.html.twig', ['product' => $product, 'variants' => $product->getVariants()]);
    }

    /**
     * @Route("/admin/product/update/{id}", methods="POST")
     */
    public function update($id, Request $request, RouterInterface $router)
    {
        $entityManager = $this->getDoctrine()->getManager();

        $productRepository = $this->getDoctrine()->getRepository(Product::class);
        $product = $productRepository->find($id);
        $product->setTitle($request->get('title'));
        $product->setDescription($request->get('description'));
        $entityManager->flush();

        $variants = $product->getVariants();
        foreach ($variants as $variant)
            $product->removeVariant($variant);

        $prices = $request->get('prices');
        $colors = $request->get('colors');
        foreach ($colors as $key => $color)
        {
            $variant = new Variant();
            $variant->setColor($color);
            $variant->setPrice($prices[$key]);
            $variant->setProduct($product);
            $entityManager->persist($variant);
        }
        $entityManager->flush();

        return new RedirectResponse($router->generate('app_product_index'));
    }

    /**
     * @Route("/product/search/{query}", methods="GET", defaults={"query" = null})
     */
    public function search($query, $finder, LoggerInterface $logger)
    {
        $client = RedisAdapter::createConnection(getenv('REDIS_URL'));
        $cache = new RedisCache($client);
        if ($cache->has($query))
            $results = $cache->get($query);
        else {
            $results = $finder->find($query);
            try {
                $cache->set($query, $results, 20);
            } catch (InvalidArgumentException $e) {
                $logger->error('Error while caching');
            }
        }
        $normalizer = new ObjectNormalizer();
        $normalizer->setCircularReferenceLimit(2);
        $normalizer->setCircularReferenceHandler(function ($object) {
            return $object->getId();
        });
        $normalizers = array($normalizer);
        $serializer = new Serializer($normalizers, array(new JsonEncoder()));
        return new JsonResponse(json_decode($serializer->serialize($results, 'json')));
    }
}