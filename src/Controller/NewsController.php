<?php

namespace App\Controller;

use http\Env\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\News;
use App\Entity\Category;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

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

    /**
     * @Route("/news/view/{id}", name="news_view")
     * @param $id int
     * @param $validator ValidatorInterface
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function view($id, ValidatorInterface $validator)
    {
        $validated = $this->validateId($id, $validator);
        if ($validated !== true) {
            return $this->render('errors.html.twig', [
                'error'  => $validated
            ]);
        }

        $news = $this->getDoctrine()->getRepository(News::class)
            ->find($id);

        return $this->render('news/view.html.twig', [
            'news'  => $news
        ]);
    }

    /**
     * @Route("/news/edit/{id}", name="news_edit")
     * @param $id int
     */
    public function edit(int $id)
    {

    }

    /**
     * @Route("/news/add", name="news_add")
     */
    public function add()
    {

    }

    /**
     * @Route("/news/delete/{id}", name="news_delete")
     * @param $id int
     */
    public function delete(int $id)
    {

    }

    /**
     * @Route("/news/category/{id}", name="news_category")
     * @param $id int
     */
    public function viewByCategory(int $id)
    {

    }

    private function validateId($id, $validator)
    {
        $idConstraint = new Assert\Positive();
        $idConstraint->message = 'Invalid id';

        $errors = $validator->validate(
            $id,
            $idConstraint
        );

        return count($errors) ? $errors[0]->getMessage() : true;
    }
}


