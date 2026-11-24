<?php
namespace App\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
class SecurityController extends AbstractController { #[Route('/login',name:'app_login')] public function login(AuthenticationUtils $a):Response{return $this->render('security/login.html.twig',['last_username'=>$a->getLastUsername(),'error'=>$a->getLastAuthenticationError()]);} #[Route('/logout',name:'app_logout')] public function logout():void{throw new \LogicException('Intercepted by firewall.');} }
