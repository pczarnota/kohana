<?php defined('SYSPATH') or die('No direct script access.');
 
class Controller_User extends Controller_Template { //dziedziczenie z Controller_Template
    public $template = 'szablon';//definiowanie zmiennej do obsługi widoków
 
    public function action_index(){
        $this->template->content='user/main'; //załadowanie widoku
        $auth = Auth::instance(); //utworzenie instancji obiektu Auth
        $whoami = $auth->get_user(); //pobieranie danych o zalogowanym użytkowniku
 
        if ($auth->logged_in('login')){ //sprawdzanie czy użytkownik jest zalogowany
        //jeśli jest to dopuszczamy (to sekcja dla zalogowanych)
 
            $this->template->wiadomosc = "Witaj $whoami->username!<br />Jesteś zalogowany.";
            //i cała reszta kodu dla autoryzowanego użytkownika
 
        }else{ //jeśli nie jest zalogowany, przekierowujemy do logowania
            $this->template->content=View::factory('user/login'); //jeśli nie, przekierowujemy do logowania
        }
    }
 
    public function action_register(){
        $this->template->content='user/register'; //załadowanie widoku
        $auth = Auth::instance(); //utworzenie instancji obiektu Auth
 
        if ($auth->logged_in('login')){ //sprawdzanie czy użytkownik jest zalogowany
            $this->template->content='user/login'; //jeśli nie, przekierowujemy do logowania
        }else{
 
            if($_POST){ //sprawdzanie czy dane są przesyłane POSTem
                $walidacja = Validation::factory($_POST); //tworzenie obiektu walidacji
                $walidacja->rule('login', 'not_empty')
                        ->rule('login', 'alpha_dash')
                        ->rule('email', 'not_empty')
                        ->rule('haslo', 'not_empty')
                        ->rule('email', 'email');
 
                if($walidacja->check()){
                    $user = ORM::factory('user'); //tworzenie obiektu ORM z użyciem tabeli users
                    $user->username = $_POST['login']; //przypisanie pola z formularza do nazwy kolumny w tabeli
                    $user->email = $_POST['email'];
                    $user->password = $_POST['haslo'];
 
                //instrukcja warunkowa/zapis danych użytkownika/przypisanie roli "login"
                if($user->save() && $user->add('roles', ORM::factory('role', array('name' => 'login'))) ){
                    $this->template->sukces = 'Dziękujemy za rejestrację!'; //przekazanie zmiennej $sukces do widoku
                }else{
                    $this->template->fail = 'Nie udało się dodać użytkownika!'; //przekazanie zmiennej $fail do widoku
                }
 
                }else{
                $this->template->fail = 'Uzupełnij poprawnie formularz rejestracyjny!';
                }
            }
 
        }
    }
 
    public function action_login(){
        $this->template->content='user/login';
        $auth = Auth::instance(); //utworzenie instancji obiektu Auth
 
        if ($auth->logged_in('login')){ //sprawdzanie czy użytkownik jest zalogowany
            $this->request->redirect('user'); //jeśli jest, przekierowujemy do user
        }else{
 
            if($_POST){
                
				$walidacja = Validation::factory($_POST);	 //tworzenie obiektu walidacji    
				$walidacja
						->rule('login', 'not_empty')
                        ->rule('login', 'alpha_dash')
                        ->rule('haslo', 'not_empty');
 
                if($walidacja->check()){ //jeśli walidacja OK to zaloguj
                   // $auth->login($_POST['login'], $_POST['haslo'], FALSE); //logowanie użytkownika
                   // $this->request->redirect('user'); //przekierowanie po zalogowaniu do kontrolera user
					if ($auth->login($_POST['login'], $_POST['haslo']))
					{
						Session::instance()->write();
						$this->request->redirect('/');
					}		


			   }else{
                    $this->template->fail = 'Uzupełnij poprawnie formularz!';
                }
 
        }
 
        }
    }
 
    public function action_logout(){
        $this->template->content = 'user/logout';
        $auth = Auth::instance(); //utworzenie instancji obiektu Auth
        if ($auth->logged_in('login')){ //sprawdzanie czy użytkownik jest zalogowany
            if($auth->logout(TRUE)){ //jeśli jest, to go wylogowujemy
                $this->template->sukces = 'Pomyślnie wylogowano!';
            }else{
                $this->request->redirect('user/login'); //przekierowujemy do logowania
            }
        }else{ //jeśli nie jest zalogowany przekierowujemy do logowania
            $this->request->redirect('user/login');
        }
    }
	}