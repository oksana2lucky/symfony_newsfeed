<?php

namespace App\Controller;

use http\Env\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\News;
use App\Entity\Category;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
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
     * @param $validator ValidatorInterface
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws
     */
    public function edit(int $id, ValidatorInterface $validator)
    {
        $validated = $this->validateId($id, $validator);
        if ($validated !== true) {
            return $this->render('errors.html.twig', [
                'error'  => $validated
            ]);
        }

        $news = $this->getDoctrine()->getRepository(News::class)
            ->find($id);

        $categories = $this->getDoctrine()->getRepository(Category::class)
            ->findAll();
        $choiceCategory = [];
        foreach($categories as $category) {
            $choiceCategory[$category->getName()] = $category->getId();
        }

        $defaults = [
            'title' => $news->getTitle(),
            'categoryAt' => $news->getCategoryId(),
            'summary' => $news->getSummary(),
            'link' => $news->getLink(),
            'postedAt' => $news->getPostedAt(),
        ];

        $form = $this->createFormBuilder($defaults)
            ->add('title', TextType::class)
            ->add('categoryId', ChoiceType::class, ['choices' => $choiceCategory])
            ->add('summary', TextareaType::class)
            ->add('link', TextType::class, ['required' => false])
            ->add('postedAt', DateType::class)
            ->getForm();

        return $this->render('news/form.html.twig', [
            'title' => 'Edit news',
            'form' => $form->createView(),
        ]);

    }

    /**
     * @Route("/news/add", name="news_add")
     */
    public function add()
    {
        $categories = $this->getDoctrine()->getRepository(Category::class)
            ->findAll();
        $choiceCategory = [];
        foreach($categories as $category) {
            $choiceCategory[$category->getName()] = $category->getId();
        }

        $defaults = [
            'postedAt' => new \DateTime('now'),
        ];

        $form = $this->createFormBuilder($defaults)
            ->add('title', TextType::class)
            ->add('categoryId', ChoiceType::class, ['choices' => $choiceCategory])
            ->add('summary', TextareaType::class)
            ->add('link', TextType::class, ['required' => false])
            ->add('postedAt', DateType::class)
            ->getForm();

        return $this->render('news/form.html.twig', [
            'title' => 'Add news',
            'form' => $form->createView(),
        ]);

    }

    /**
     * @Route("/news/delete/{id}", name="news_delete")
     * @param $id int
     * @param $validator ValidatorInterface
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function delete(int $id, ValidatorInterface $validator)
    {
        $validated = $this->validateId($id, $validator);
        if ($validated !== true) {
            return $this->render('errors.html.twig', [
                'error'  => $validated
            ]);
        }

        $entityManager = $this->getDoctrine()->getManager();
        $news = $this->getDoctrine()->getRepository(News::class)
            ->find($id);
        $entityManager->remove($news);
        $entityManager->flush();

        return $this->redirectToRoute('news');

    }

    /**
     * @Route("/news/category/{id}", name="news_category")
     * @param $id int
     * @param $validator ValidatorInterface
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function viewByCategory(int $id, ValidatorInterface $validator)
    {
        $validated = $this->validateId($id, $validator);
        if ($validated !== true) {
            return $this->render('errors.html.twig', [
                'error'  => $validated
            ]);
        }

        $categories = $this->getDoctrine()->getRepository(Category::class)
            ->findAll();

        foreach($categories as $category) {
            if ($category->getId() == $id) {
                $categoryName = $category->getName();
                break;
            }
        }

        $news = $this->getDoctrine()->getRepository(News::class)
            ->findBy(
                ['categoryId' => $id],
                ['postedAt' => 'DESC']
            );

        return $this->render('news/category.html.twig', [
            'title' => $categoryName ?? '',
            'categories' => $categories,
            'news'  => $news
        ]);
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


