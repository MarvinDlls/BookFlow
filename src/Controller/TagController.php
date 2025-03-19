<?php

namespace App\Controller;

use App\Entity\Tag;
use App\Form\TagType;
use App\Repository\TagRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Controller utilisé pour la gestion des tags.
 */
#[Route('/tag')]
final class TagController extends AbstractController
{
    private TagRepository $tagRepository;
    private EntityManagerInterface $entityManager;

    /**
     * Injecte les dépendances nécessaires pour la gestion des tags.
     *
     * @param TagRepository $tagRepository
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(TagRepository $tagRepository, EntityManagerInterface $entityManager)
    {
        $this->tagRepository = $tagRepository;
        $this->entityManager = $entityManager;
    }

    /**
     * Permet d'afficher la liste des tags.
     *
     * @return Response
     */
    #[Route(name: 'app_tag_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('tag/index.html.twig', [
            'tags' => $this->tagRepository->findAll(),
        ]);
    }

    /**
     * Permet de créer un nouveau tag.
     *
     * @param Request $request
     * @return Response
     */
    #[Route('/new', name: 'app_tag_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $tag = new Tag();
        $form = $this->createForm(TagType::class, $tag);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($tag);
            $this->entityManager->flush();

            return $this->redirectToRoute('app_tag_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('tag/new.html.twig', [
            'tag' => $tag,
            'form' => $form,
        ]);
    }

    /**
     * Permet d'afficher un tag.
     *
     * @param Tag $tag
     * @return Response
     */
    #[Route('/{id}', name: 'app_tag_show', methods: ['GET'])]
    public function show(Tag $tag): Response
    {
        return $this->render('tag/show.html.twig', [
            'tag' => $tag,
        ]);
    }

    /**
     * Permet de modifier un tag.
     *
     * @param Request $request
     * @param Tag $tag
     * @return Response
     */
    #[Route('/{id}/edit', name: 'app_tag_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Tag $tag): Response
    {
        $form = $this->createForm(TagType::class, $tag);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            return $this->redirectToRoute('app_tag_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('tag/edit.html.twig', [
            'tag' => $tag,
            'form' => $form,
        ]);
    }

    /**
     * Permet de supprimer un tag.
     *
     * @param Request $request
     * @param Tag $tag
     * @return Response
     */
    #[Route('/{id}', name: 'app_tag_delete', methods: ['POST'])]
    public function delete(Request $request, Tag $tag): Response
    {
        if ($this->isCsrfTokenValid('delete'.$tag->getId(), $request->getPayload()->getString('_token'))) {
            $this->entityManager->remove($tag);
            $this->entityManager->flush();
        }

        return $this->redirectToRoute('app_tag_index', [], Response::HTTP_SEE_OTHER);
    }
}
