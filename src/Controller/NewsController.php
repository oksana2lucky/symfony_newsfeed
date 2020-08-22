<?php

namespace App\Controller;

use http\Env\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\News;
use App\Entity\Category;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;


class NewsController extends AbstractController
{
    /**
     * Show general news list
     *
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
     * Show news page
     *
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
     * Show form for editing news
     *
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
            'category_id' => $news->getCategoryId(),
            'summary' => $news->getSummary(),
            'link' => $news->getLink(),
            'posted_at' => $news->getPostedAt(),
        ];

        $form = $this->createFormBuilder($defaults, [
                'action' => $this->generateUrl('news_save'),
                'method' => 'POST'
            ])
            ->add('title', TextType::class)
            ->add('category_id', ChoiceType::class, ['choices' => $choiceCategory])
            ->add('summary', TextareaType::class)
            ->add('link', TextType::class, ['required' => false])
            ->add('posted_at', DateType::class)
            ->add('news_id', HiddenType::class, ['data' => $id])
            ->add('save', SubmitType::class, ['label' => 'Save'])
            ->getForm();

        return $this->render('news/form.html.twig', [
            'title' => 'Edit news',
            'form' => $form->createView(),
        ]);

    }

    /**
     * Show form for adding news
     *
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

        $form = $this->createFormBuilder($defaults, [
                'action' => $this->generateUrl('news_save'),
                'method' => 'POST'
            ])
            ->add('title', TextType::class)
            ->add('category_id', ChoiceType::class, ['choices' => $choiceCategory])
            ->add('summary', TextareaType::class)
            ->add('link', TextType::class, ['required' => false])
            ->add('posted_at', DateType::class)
            ->add('save', SubmitType::class, ['label' => 'Save'])
            ->getForm();

        return $this->render('news/form.html.twig', [
            'title' => 'Add news',
            'form' => $form->createView(),
        ]);

    }

    /**
     * Delete news
     *
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
     * Shows news list filtered by category
     *
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

    /**
     * Saves edited/new news object
     *
     * @Route("/news/save", name="news_save")
     * @param $request Request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws
     */
    public function save(Request $request)
    {
        $data = $request->request->get('form');
        $id = $data['news_id'] ?? null;

        if ($id) {
            $news = $this->getDoctrine()->getRepository(News::class)
                ->find($id);
        } else {
            $news = new News();
        }

        $category = $this->getDoctrine()->getRepository(Category::class)
            ->find($data['category_id']);

        $postedAt = $data['posted_at']['year'] .'-'.$data['posted_at']['month'].'-'.$data['posted_at']['day'] ?? 'now';

        $news->setTitle($data['title'] ?? '');
        $news->setCategoryId($category);
        $news->setSummary($data['summary'] ?? '');
        $news->setLink($data['link'] ?? null);
        $news->setPostedAt(new \DateTime($postedAt));
        $this->getDoctrine()->getManager()->persist($news);
        $this->getDoctrine()->getManager()->flush();

        return $this->redirectToRoute('news_view', ['id' => $news->getId()]);
    }

    /**
     * Validates id param whether positive number it is
     *
     * @param $id int
     * @param $validator ValidatorInterface
     * @return bool|string
     */
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


