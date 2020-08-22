<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\News;
use App\Entity\Category;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateType;

class NewsController extends AbstractController
{
    /**
     * @Route("/news", name="news")
     */
    public function index()
    {
        $news = $this->getDoctrine()->getRepository(News::class)
            ->findBy(
                [],
                ['postedAt' => 'DESC'],
                10
            );

        $categories = $this->getDoctrine()->getRepository(Category::class)
            ->findAll();

        return $this->render('news/index.html.twig', [
            'categories' => $categories,
            'news'  => $news
        ]);
    }
}


