<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Welcome extends Controller_Template {
 
        public $template = 'szablon';
   
    public function action_index()
    {
	 $auth = Auth::instance(); //utworzenie instancji obiektu Auth
        $whoami = $auth->get_user(); //pobieranie danych o zalogowanym użytkowniku
 
        if ($auth->logged_in('login')){ 

	  $this->template->content='home';
    
	$this->template->jest = 'xxxxx'.$this->request->uri();
	
        $this->template->kto = $whoami->username; 
        
$this->template->konta = ORM::factory('firm')->where('niewidoczna', '=', '0')->find_all();   


if(isset($_POST)){
    $this->session = Session::instance();
    $this->session->set('konto', $_POST['konto']);
}


	}else{ //jeśli nie jest zalogowany, przekierowujemy do logowania
            $this->request->redirect('user/login'); //jeśli nie, przekierowujemy do logowania
        }
	}
        
  public function action_pobierz()
    {      
          $this->template->content='pusta';
                $a = file_get_contents('http://cw.money.pl/kursy_nbp.html', true);
                $d = explode('<tr>', $a);
				$datka = explode('</td>', $d[1]);
				$dateczka = substr($datka[1], -10, 10);
                $b = explode('</td>', $d[3]);
                $c = substr($b[1], -6, 6);
                $kurs = str_replace(',', '.', $c);
                $kursy = ORM::factory('kur');
                $kursy->data = $dateczka;
                $kursy->kurs = $kurs;
                $kursy->save();

                $b1 = explode('</td>', $d[5]);
                $c1 = substr($b1[1], -6, 6);
                $kurs1 = str_replace(',', '.', $c1);
                $kursy1 = ORM::factory('kure');
                $kursy1->data = $dateczka;
                $kursy1->kurs = $kurs1;
                $kursy1->save();
    }
 
}