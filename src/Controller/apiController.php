<?php
    namespace App\Controller;
    
    use Symfony\Component\HttpFoundation\Response;
    use Symfony\Component\Routing\Annotation\Route;
    use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
    use Symfony\Bundle\FrameworkBundle\Controller\Controller;
    
    class apiController extends Controller {
        
        /**
         * @Route("/getContacts/{status}/{sortBy}")
         * @Method({"GET"})
         */
        public function contacts($status='all', $sortBy ='default'){
            $results        = array();
            $status         = strtolower($status);
            $valid_statuses = array('all', 'active', 'inactive');
            if(!in_array($status, $valid_statuses)){
                $results['error'] = 'Status must be active, inactive or all!';
            } else {
                $results       = array();
                $path          = $this->get('kernel')->getRootDir();
                $accounts_json = file_get_contents($path.'\accounts.json');
                $accounts      = json_decode($accounts_json, true);
                foreach($accounts AS $account){
                    foreach($account AS $row){
                        foreach($row['contacts'] AS $contact){
                            if($status == 'active' && $contact['active']){
                                $result[] = $contact;
                            } else if($status == 'inactive' && !$contact['active']){
                                $result[] = $contact;
                            } else if($status == 'all'){
                                $result[] = $contact;
                            }
                        }
                    }
                }
                usort($result, function ($a, $b) use ($sortBy) {
                    if(isset($a[$sortBy])){
                        return strnatcmp($a[$sortBy], $b[$sortBy]);
                    }
                });
                foreach($result AS $row){
                    $tempArray = array();
                    $tempArray['accountId'] = $row['accountId'];
                    $tempArray['numberContacts'] = count($row['phoneNumbers']);
                    $tempArray['id'] = $row['id'];
                    $tempArray['firstName'] = $row['firstName'];
                    $tempArray['lastName'] = $row['lastName'];
                    $tempArray['email'] = $row['email'];
                    $tempArray['phoneNumbers'] = $row['phoneNumbers'];
                    //if you want to see these values in sort order
                    // $tempArray['active'] = $row['active'];
                    // $tempArray['city'] = $row['city'];
                    // $tempArray['state'] = $row['state'];
                    // $tempArray['state'] = $row['state'];
                    // $tempArray['postalCode'] = $row['postalCode'];
                    $results[] = $tempArray;
                }
            }
            $response = new Response(
                'Content',
                Response::HTTP_OK,
                ['content-type' => 'json']
            );

            return $response->setContent(json_encode($results));
        }

        /**
         * @Route("/activeAccounts")
         * @Method({"GET"})
         */
        public function activeAccounts() {
            $results       = array();
            $path          = $this->get('kernel')->getRootDir();
            $accounts_json = file_get_contents($path.'\accounts.json');
            $accounts      = json_decode($accounts_json, true);
            foreach($accounts AS $row){
                foreach($row AS $account){
                    if($account['active']){
                        $results[] = $account;
                    }
                }
            }
            $response = new Response(
                'Content',
                Response::HTTP_OK,
                ['content-type' => 'json']
            );

            return $response->setContent(json_encode($results));
        }

        /**
         * @Route("/findPrimaryContact/{accountId}")
         * @Method({"GET"})
         */
        public function primaryContact($accountId = null){
            $results   = array();
            $accountId = (int)$accountId;
            if(empty($accountId)){
                $results['error'] = 'Enter a valid AccountId!';
            } else {
                $results       = array();
                $path          = $this->get('kernel')->getRootDir();
                $accounts_json = file_get_contents($path.'\accounts.json');
                $accounts      = json_decode($accounts_json, true);
                //$primary_found = false;
                foreach($accounts AS $row){
                    foreach($row AS $account){
                        if($account['id'] == $accountId){                           
                            foreach($account['contacts'] AS $contact){
                                foreach($contact['phoneNumbers'] AS $contact_number){                                        
                                    if($contact_number['primary']){
                                        //some accounts have more than one primary phone number/contact
                                        $results[] = $contact;
                                    }
                                }
                            }
                        }
                    }
                }
                if(empty($results)){
                    $results['error'] = "AccountId ".$accountId." not found or no Primary Contact on the account!";
                }
            }       
            $response = new Response(
                'Content',
                Response::HTTP_OK,
                ['content-type' => 'json']
            );

            return $response->setContent(json_encode($results));
        }
    }