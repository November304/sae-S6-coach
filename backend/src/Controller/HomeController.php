<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(AuthorizationCheckerInterface $authChecker): RedirectResponse
    {
        if (!$authChecker->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->redirectToRoute('app_login');
        }

        $user = $this->getUser();

        if (in_array('ROLE_RESPONSABLE', $user->getRoles(), true)) {
            return $this->redirectToRoute('admin');
        }
        if (in_array('ROLE_COACH', $user->getRoles(), true)) {
            return $this->redirectToRoute('coach_admin');
        }

        //TODO : Pr tous les autres roles on les met sur une page d'erreur
        return $this->redirectToRoute('app_login');
    }
}
