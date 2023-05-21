<?php

namespace App\Controller\Admin;

use App\Entity\Images;
use App\Entity\Products;
use App\Form\ProductsFormType;
use App\Service\PictureService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('admin/produits', 'admin_products_')]
class ProductsController extends AbstractController
{
    #[Route('/', 'index')]
    public function index(): Response
    {
        return $this->render('admin/products/index.html.twig');
    }
    
    #[Route('/ajout', 'add')]
    public function add(Request $request, EntityManagerInterface $em, SluggerInterface $slugger, PictureService $pictureService): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $product = new Products();
        $productForm = $this->createForm(ProductsFormType::class, $product);

        $productForm->handleRequest($request);
        if($productForm->isSubmitted() && $productForm->isValid()){
            // gestion téléchargement images
            $images = $productForm->get('images')->getData();

            foreach ($images as $image) {
                $folder = 'products';
                $file = $pictureService->add($image, $folder, 300, 300);

                $img = new Images();
                $img->setName($file);
                $product->addImage($img);
            }

            // gestion reste formulaire
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
    public function edit(Products $product, Request $request, EntityManagerInterface $em, SluggerInterface $slugger, PictureService $pictureService): Response
    {
        $this->denyAccessUnlessGranted('PRODUCT_EDIT', $product);

        $price = $product->getPrice() / 100;
        $product->setPrice($price);

        $productForm = $this->createForm(ProductsFormType::class, $product);

        $productForm->handleRequest($request);
        if($productForm->isSubmitted() && $productForm->isValid()){
            // gestion téléchargement images
            $images = $productForm->get('images')->getData();

            foreach ($images as $image) {
                $folder = 'products';
                $file = $pictureService->add($image, $folder, 300, 300);

                $img = new Images();
                $img->setName($file);
                $product->addImage($img);
            }
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
            'productForm' => $productForm->createView(),
            'product' => $product
        ]);
    }
    
    #[Route('/suppression/{id}', 'delete')]
    public function delete(Products $product): Response
    {
        $this->denyAccessUnlessGranted('PRODUCT_DELETE', $product);
        return $this->render('admin/products/delete.html.twig');
    }

    #[Route('/suppression/image/{id}', 'delete_image', methods: ['DELETE'])]
    public function deleteImage(Images $image, Request $request, EntityManagerInterface $em, PictureService $pictureService): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if($this->isCsrfTokenValid('delete' . $image->getId(), $data['_token'])){
            $name = $image->getName();
            if($pictureService->delete($name, 'products', 300, 300)){
                $em->remove($image);
                $em->flush();

                return new JsonResponse(['success' => true], 200);
            }
            return new JsonResponse(['error' => 'Erreur lors de la suppression.'], 400);
        }

        return new JsonResponse(['error' => 'Token invalide.'], 400);
    }
}