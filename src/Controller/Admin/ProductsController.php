<?php

namespace App\Controller\Admin;

use App\Entity\Products;
use App\Form\ProductsFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('admin/produits', 'admin_products_')]
class ProductsController extends AbstractController
{
    #[Route('/', 'index')]
    public function index(): Response
    {
        return $this->render('admin/products/index.html.twig');
    }
    
    #[Route('/ajout', 'add')]
    public function add(Request $request, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $product = new Products();
        $productForm = $this->createForm(ProductsFormType::class, $product);

        $productForm->handleRequest($request);
        if($productForm->isSubmitted() && $productForm->isValid()){
            $images = $productForm->get('images')->getData();

            $slug = $slugger->slug($product->getName())->lower();
            $product->setSlug($slug);

            $price = $product->getPrice() * 100;
            $product->setPrice($price);

            $em->persist($product);
            $em->flush();

            $this->addFlash('success', 'Produit enregistré.');

            return $this->redirectToRoute('admin_products_index');
        }

        return $this->render('admin/products/add.html.twig',[
            'productForm' => $productForm->createView()
        ]);
    }

    #[Route('/edition/{id}', 'edit')]
    public function edit(Products $product, Request $request, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        $this->denyAccessUnlessGranted('PRODUCT_EDIT', $product);

        $price = $product->getPrice() / 100;
        $product->setPrice($price);

        $productForm = $this->createForm(ProductsFormType::class, $product);

        $productForm->handleRequest($request);
        if($productForm->isSubmitted() && $productForm->isValid()){
            $slug = $slugger->slug($product->getName())->lower();
            $product->setSlug($slug);

            $price = $product->getPrice() * 100;
            $product->setPrice($price);

            $em->persist($product);
            $em->flush();

            $this->addFlash('success', 'Produit modifié.');

            return $this->redirectToRoute('admin_products_index');
        }

        return $this->render('admin/products/edit.html.twig',[
            'productForm' => $productForm->createView()
        ]);
    }
    
    #[Route('/suppression/{id}', 'delete')]
    public function delete(Products $product): Response
    {
        $this->denyAccessUnlessGranted('PRODUCT_DELETE', $product);
        return $this->render('admin/products/delete.html.twig');
    }
}