<?php

namespace App\Controller;


use App\Entity\Product;
use App\Entity\Variant;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;

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
     * @Route("/product/show/{id}", methods="GET")
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
}