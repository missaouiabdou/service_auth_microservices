<?php

namespace App\Presentation\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ViewController extends AbstractController
{
    #[Route('/', name: 'app_view')]
    public function index(): Response
    {
        return $this->render('view/index.html.twig', [
            'controller_name' => 'ViewController',
        ]);
    }
}
