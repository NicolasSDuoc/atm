<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Firebase\Auth\Token\Exception\InvalidToken;
use Kreait\Firebase\Auth;
use Symfony\Component\HttpFoundation\Request;
use Kreait\Firebase\Exception\FirebaseException;
use Kreait\Firebase\Exception\Auth\RevokedIdToken;
use Throwable;

class ApiUserController extends AbstractController
{

    public function __construct(Auth $auth)
    {
        $this->auth = $auth;
    }

    /**
     * @Route("/api/user/login", name="api_user_login", methods={"POST"})
     */
    public function logIn(Request $request): Response
    { 

        $data = json_decode($request->getContent(), true);

        $email = $data['email'];
        $password = $data['password'];

        try{
            $signInResult = $this->auth->signInWithEmailAndPassword($email, $password);

            $idTokenString = $signInResult->idToken();
            $verifiedIdToken = $this->auth->verifyIdToken($idTokenString);
            $user = $this->auth->getUser($signInResult->firebaseUserId());
            
            return $this->json(['user' => $user,'token' => $idTokenString, 'refreshToken' => $signInResult->refreshToken()]);

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


    /**
     * @Route("/api/user/revtoken", name="api_user_revtoken", methods={"POST"})
     */
    public function logOut(Request $request): Response
    { 

        $data = json_decode($request->getContent(), true);

        $idTokenString = $data['token'];

        $this->auth->revokeRefreshTokens($data['uid']);

        try {
            $verifiedIdToken = $this->auth->verifyIdToken($idTokenString, $checkIfRevoked = true);
        } catch (RevokedIdToken $e) {
            return $this->json(['status' => 'success', 'message' => $e->getMessage()]);
        }
       
    }

    /**
     * @Route("/api/user/createuser", name="api_user_createuser", methods={"POST"})
     */
    public function createUser(Request $request): Response
    { 

        $data = json_decode($request->getContent(), true);

        $userProperties = [
            'email' => $data['email'],
            'emailVerified' => false,
            'phoneNumber' => $data['phoneNumber'],
            'password' => $data['password'],
            'displayName' => $data['displayName'],
            'disabled' => false,
        ];
        
        
        try{

            $createdUser = $this->auth->createUser($userProperties);

            $uid = $createdUser->uid;

            if($data['userType'] == 'admin')
            {
                $this->auth->setCustomUserClaims($uid, ['type' => 'admin']);
            }
            elseif($data['userType'] == 'customer')
            {
                $this->auth->setCustomUserClaims($uid, ['type' => 'customer']);
            }

            return $this->json(['status' => 'success', 'user' => $createdUser]);

        } catch (FirebaseException $e) {
            return $this->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
       
    }

    /**
     * @Route("/api/user/disableuser", name="api_user_disableuser", methods={"POST"})
     */
    public function disableUser(Request $request): Response
    { 

        $data = json_decode($request->getContent(), true);

        $uid = $data['uid'];
        
        try{

        $this->auth->disableUser($uid);

        return $this->json(['status' => 'success', 'message' => 'Usuario deshabilitado']);

        } catch (FirebaseException $e) {
            return $this->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
       
    }

    /**
     * @Route("/api/user/enableuser", name="api_user_enableuser", methods={"POST"})
     */
    public function enableUser(Request $request): Response
    { 

        $data = json_decode($request->getContent(), true);

        $uid = $data['uid'];
        
        try{

        $this->auth->enableUser($uid);

        return $this->json(['status' => 'success', 'message' => 'Usuario habilitado']);

        } catch (FirebaseException $e) {
            return $this->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
       
    }

    /**
     * @Route("/api/user/resetpwd", name="api_user_resetpwd", methods={"POST"})
     */
    public function resetPwd(Request $request): Response
    { 

        $data = json_decode($request->getContent(), true);
        $customSettings = array();

        if(array_key_exists('customSettings', $data))
        {
            $customSettings[] = $data['customSettings'];
        }

        $email = $data['email'];
        
        try{

            $this->auth->sendPasswordResetLink($email, $customSettings);

        return $this->json(['status' => 'success', 'message' => 'Correo de recuperacion enviado']);

        } catch (FirebaseException $e) {
            return $this->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
       
    }

    /**
     * @Route("/api/user/listusers", name="api_user_listusers", methods={"GET"})
     */
    public function listUsers(Request $request): Response
    {   
        try{

        $users = $this->auth->listUsers();
        $userlist = array();
        foreach($users as $user)
        {
            $userlist[] = $this->auth->getUser($user->uid);
        }

        return $this->json(['list' => $userlist]);

        } catch (FirebaseException $e) {
            return $this->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
       
    }


    // ESTA WEAAA TIRA UN ERROR QLAO RARO NO CAXO
    
//    /**
//      * @Route("/api/user/getuser/{uid}", name="api_user_getuser")
//      */
//     public function getUser(Request $request, $uid): Response
//     {   
//         try{

//             $user = $this->auth->getUser($uid);

//         return $this->json(['user' => $user]);

//         } catch (FirebaseException $e) {
//             return $this->json(['status' => 'error', 'message' => $e->getMessage()]);
//         }
       
//     }

}