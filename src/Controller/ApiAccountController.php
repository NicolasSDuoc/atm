<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Firebase\Auth\Token\Exception\InvalidToken;
use Kreait\Firebase\Auth;
use Kreait\Firebase\Database;
use Symfony\Component\HttpFoundation\Request;
use Kreait\Firebase\Exception\FirebaseException;
use Kreait\Firebase\Exception\Auth\RevokedIdToken;
use Throwable;

class ApiAccountController extends AbstractController
{

    public function __construct(Auth $auth, Database $database)
    {
        $this->auth = $auth;
        $this->database = $database;
    }

    /**
     * @Route("/api/account/create", name="api_account_create", methods={"POST"})
     */
    public function createAccount(Request $request): Response
    { 

        $data = json_decode($request->getContent(), true);

        try{
            $uidclient = $data['uidclient'];
            $token = $data['token'];
            $user = $this->auth->getUser($uidclient);
            $verifiedIdToken = $this->auth->verifyIdToken($token, $checkIfRevoked = true);
            $claims = $this->auth->getUser($uidclient)->customClaims;

            $exists =  $this->database->getReference('accounts/' . $uidclient)->getSnapshot()->exists();
            if($exists)
            {
                return $this->json(['status' => 'you_stupid', 'message' => 'El usuario ya tiene cuenta bancaria']);
            }
            else{
                if($claims['type'] == "customer")
                {
            $this->database->getReference('accounts/' . $uidclient)
            ->set([
                    'created_at' => ['.sv' => 'timestamp'],
                    'current_balance' => '5000',
                    'status' => 'active'
            ]);
            }

            else{
                return $this->json(['status' => 'error', 'message' => 'Solo los clientes pueden tener cuenta bancaria']);
            }

            }

            return $this->json(['status' => 'success', 'message' => 'Cuenta bancaria creada']);

            } catch (FirebaseException $e) {
                return $this->json(['status' => 'error', 'message' => $e->getMessage()]);
            } catch (InvalidToken $e) {
                return $this->json(['status' => 'relog', 'message' => 'Llave invalida: '.$e->getMessage()]);
            } catch (\InvalidArgumentException $e) {
                return $this->json(['status' => 'error', 'message' => 'La llave no se logro leer: '.$e->getMessage()]);
            }
    }

    /**
     * @Route("/api/account/disable", name="api_account_disable", methods={"POST"})
     */
    public function disableAccount(Request $request): Response
    { 

        $data = json_decode($request->getContent(), true);

        try{
            $uidclient = $data['uidclient'];
            $uiduser = $data['uiduser'];
            $token = $data['token'];
            $client = $this->auth->getUser($uidclient);
            $user = $this->auth->getUser($uiduser);
            $verifiedIdToken = $this->auth->verifyIdToken($token, $checkIfRevoked = true);
            

            $exists =  $this->database->getReference('accounts/' . $uidclient)->getSnapshot()->exists();
            if($exists)
            {
                $updates = [
                    'status' => 'inactive',
                    'disabled_by' => $user->displayName
                ];

                $this->database->getReference('accounts/' . $uidclient)->update($updates);

                return $this->json(['status' => 'success', 'message' => 'Cuenta bancaria bloqueada']);
            }
            else{
                return $this->json(['status' => 'error', 'message' => 'La cuenta no existe']);
            }

            } catch (FirebaseException $e) {
                return $this->json(['status' => 'error', 'message' => $e->getMessage()]);
            } catch (InvalidToken $e) {
                return $this->json(['status' => 'relog', 'message' => 'Llave invalida: '.$e->getMessage()]);
            } catch (\InvalidArgumentException $e) {
                return $this->json(['status' => 'error', 'message' => 'La llave no se logro leer: '.$e->getMessage()]);
            }
    }


    /**
     * @Route("/api/account/get", name="api_account_get", methods={"POST"})
     */
    public function getAccountData(Request $request): Response
    { 

        $data = json_decode($request->getContent(), true);

        try{
            $uidclient = $data['uidclient'];
            $uiduser = $data['uiduser'];
            $token = $data['token'];
            $client = $this->auth->getUser($uidclient);
            $claims = $this->auth->getUser($uiduser)->customClaims;

            $verifiedIdToken = $this->auth->verifyIdToken($token, $checkIfRevoked = true);
            

            $exists =  $this->database->getReference('accounts/' . $uidclient)->getSnapshot()->exists();
            if($exists)
            {

               $account = $this->database->getReference('accounts/' . $uidclient)->getValue();

                if($claims['type'] == "admin")
                {
                    $account_send = array();
                    $account_send[$client->uid] =[
                        'created_at' => $account['created_at'],
                        'status' => $account['status']
                    ];
                }
                else{
                    $account_send = $account;
                }

                return $this->json(['account' => $account_send]);
            }
            else{
                return $this->json(['status' => 'error', 'message' => 'Cuenta no existe']);
            }

            return $this->json(['status' => 'success', 'message' => 'Cuenta bancaria creada']);

            } catch (FirebaseException $e) {
                return $this->json(['status' => 'error', 'message' => $e->getMessage()]);
            } catch (InvalidToken $e) {
                return $this->json(['status' => 'relog', 'message' => 'Llave invalida: '.$e->getMessage()]);
            } catch (\InvalidArgumentException $e) {
                return $this->json(['status' => 'error', 'message' => 'La llave no se logro leer: '.$e->getMessage()]);
            }
    }

    /**
     * @Route("/api/account/withdrawal", name="api_account_withdrawal", methods={"POST"})
     */
    public function withdrawal(Request $request): Response
    { 

        $data = json_decode($request->getContent(), true);

        try{
            $uidclient = $data['uidclient'];
            $uiduser = $data['uiduser'];
            $token = $data['token'];
            $amount = $data['amount'];
            $client = $this->auth->getUser($uidclient);
            $claims = $this->auth->getUser($uiduser)->customClaims;

            $verifiedIdToken = $this->auth->verifyIdToken($token, $checkIfRevoked = true);
            
            $exists =  $this->database->getReference('accounts/' . $uidclient)->getSnapshot()->exists();
            if($exists)
            {

               $account = $this->database->getReference('accounts/' . $uidclient)->getValue();

                if($claims['type'] == "customer")
                {
                    if($uiduser == $uidclient)
                    {
                       if($account['current_balance'] < $amount)
                       {
                        return $this->json(['status' => 'error', 'message' => 'Dinero insuficiente en la cuenta, broke nibba']);
                       }
                       else{

                        $old_balance = $account['current_balance'];
                        $new_balance = $account['current_balance']-$amount;

                        $newKey = $this->database->getReference('transactions/' . $uidclient)->push()->getKey();

                        $this->database->getReference('transactions/' . $uidclient . '/' . $newKey)
                        ->set([
                                'date' => ['.sv' => 'timestamp'],
                                'old_balance' => $old_balance,
                                'new_balance' => $new_balance,
                                'type' => 'withdrawal'
                        ]);
                        
                        $updates = [
                            'current_balance' => $new_balance
                        ];

                        $this->database->getReference('accounts/' . $uidclient)->update($updates);


                        return $this->json(['status' => 'success', 'message' => 'Retirado realizado', 'newBalance' => $new_balance]);
                    }
                        
                    }
                    else{
                        return $this->json(['status' => 'error', 'message' => 'Usted no tiene acceso a esta cuenta']);
                    }
                    
                }
                else{
                    return $this->json(['status' => 'error', 'message' => 'Solo el cliente puede realizar retiros']);
                }
            }
            else{
                return $this->json(['status' => 'error', 'message' => 'Cuenta no existe']);
            }

            } catch (FirebaseException $e) {
                return $this->json(['status' => 'error', 'message' => $e->getMessage()]);
            } catch (InvalidToken $e) {
                return $this->json(['status' => 'relog', 'message' => 'Llave invalida: '.$e->getMessage()]);
            } catch (\InvalidArgumentException $e) {
                return $this->json(['status' => 'error', 'message' => 'La llave no se logro leer: '.$e->getMessage()]);
            }
    }

    /**
     * @Route("/api/account/deposit", name="api_account_deposit", methods={"POST"})
     */
    public function deposit(Request $request): Response
    { 

        $data = json_decode($request->getContent(), true);

        try{
            $uidclient = $data['uidclient'];
            $uiduser = $data['uiduser'];
            $token = $data['token'];
            $amount = $data['amount'];
            $client = $this->auth->getUser($uidclient);
            $claims = $this->auth->getUser($uiduser)->customClaims;

            $verifiedIdToken = $this->auth->verifyIdToken($token, $checkIfRevoked = true);
            
            $exists =  $this->database->getReference('accounts/' . $uidclient)->getSnapshot()->exists();
            if($exists)
            {

               $account = $this->database->getReference('accounts/' . $uidclient)->getValue();

                if($claims['type'] == "customer")
                {
                    if($uiduser == $uidclient)
                    {
                       if($amount > 3000000)
                       {
                        return $this->json(['status' => 'error', 'message' => 'El monto no puede ser mayor a $3.000.000, banco para probres vro']);
                       }
                       else{
                        
                        $old_balance = $account['current_balance'];
                        $new_balance = $account['current_balance'] + $amount;

                        $newKey = $this->database->getReference('transactions/' . $uidclient)->push()->getKey();

                        $this->database->getReference('transactions/' . $uidclient . '/' . $newKey)
                        ->set([
                                'date' => ['.sv' => 'timestamp'],
                                'old_balance' => $old_balance,
                                'new_balance' => $new_balance,
                                'type' => 'deposit'
                        ]);

                        $updates = [
                            'current_balance' => $new_balance
                        ];

                        $this->database->getReference('accounts/' . $uidclient)->update($updates);

                        return $this->json(['status' => 'success', 'message' => 'Ingreso realizado', 'newBalance' => $new_balance]);
                    }
                        
                    }
                    else{
                        return $this->json(['status' => 'error', 'message' => 'Usted no tiene acceso a esta cuenta']);
                    }
                    
                }
                else{
                    return $this->json(['status' => 'error', 'message' => 'Solo el cliente puede realizar retiros']);
                }

            }
            else{
                return $this->json(['status' => 'error', 'message' => 'Cuenta no existe']);
            }

            } catch (FirebaseException $e) {
                return $this->json(['status' => 'error', 'message' => $e->getMessage()]);
            } catch (InvalidToken $e) {
                return $this->json(['status' => 'relog', 'message' => 'Llave invalida: '.$e->getMessage()]);
            } catch (\InvalidArgumentException $e) {
                return $this->json(['status' => 'error', 'message' => 'La llave no se logro leer: '.$e->getMessage()]);
            }
    }

}