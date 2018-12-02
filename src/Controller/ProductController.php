<?php

namespace App\Controller;


use App\Entity\Product;
use App\Entity\Variant;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ProductController extends AbstractController
{
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
    public function store(Request $request)
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
    }
}