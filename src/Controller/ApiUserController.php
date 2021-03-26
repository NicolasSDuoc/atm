<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Firebase\Auth\Token\Exception\InvalidToken;
use Kreait\Firebase\Auth;
use Symfony\Component\HttpFoundation\Request;
use Kreait\Firebase\Exception\FirebaseException;
use Throwable;

class ApiUserController extends AbstractController
{

    public function __construct(Auth $auth)
    {
        $this->auth = $auth;
    }

    /**
     * @Route("/api/login", name="api_login")
     */
    public function index(Request $request): Response
    { 

        $data = json_decode($request->getContent(), true);

        $email = $data['email'];
        $password = $data['password'];

        try{
            $signInResult = $this->auth->signInWithEmailAndPassword($email, $password);

            $idTokenString = $signInResult->idToken();
            $verifiedIdToken = $this->auth->verifyIdToken($idTokenString);
            $user = $this->auth->getUser($signInResult->firebaseUserId());
            
            return $this->json(['output' => $user]);

            } catch (FirebaseException $e) {
                return $this->json(['status' => 'error', 'message' => $e->getMessage()]);
            } catch (InvalidToken $e) {
                return $this->json(['status' => 'error', 'message' => 'Llave invalida: '.$e->getMessage()]);
            } catch (\InvalidArgumentException $e) {
                return $this->json(['status' => 'error', 'message' => 'La llave no se logro leer: '.$e->getMessage()]);
            } catch (Throwable $e) {
                return $this->json(['status' => 'error', 'message' => $e->getMessage()]);
            }
    }

}