<?php

defined('SYSPATH') or die('No direct script access.');

class Controller_Listing extends Controller_Template { //dziedziczenie z Controller_Template

    public $template = 'szablon'; //definiowanie zmiennej do obsługi widoków

    public function action_koszty() {
        $this->template->content = 'lkoszty'; //załadowanie widoku
        $auth = Auth::instance(); //utworzenie instancji obiektu Auth
        $this->template->jest = 'xxxxxxxx' . $this->request->uri();


        if (!$auth->logged_in('login')) { //sprawdzanie czy użytkownik jest zalogowany
            $this->request->redirect('user/login'); //jeśli nie, przekierowujemy do logowania
        } else {

            $this->session = Session::instance();
            $cos = $this->session->get('konto');
            $a = 2;
            $this->template->koszty = ORM::factory('cost')->where('konto', '=', $cos)->order_by('data', 'DESC')->find_all();

            if ($_POST) {
                if (isset($_POST['id'])) {
                    foreach ($_POST['id'] as $id) {
                        $usun = ORM::factory('cost')->where('id', '=', $id)->find();
                        $usun->delete();
                    }
                    $this->template->koszty = ORM::factory('cost')->where('konto', '=', $cos)->find_all();
                } else {
                    $order = $this->session->get('order');
                    $a = $this->session->get('a');
                    if ($order == $_POST['order'] && $a != 1) {
                        $a = 1;
                        $this->session->set('a', $a);
                    } else if ($order == $_POST['order'] && $a == 1) {
                        $a = 2;
                        $this->session->set('a', $a);
                    }
                    $order = $_POST['order'];
                    $this->session->set('order', $order);
                    if ($a != 1) {
                        $this->template->koszty = ORM::factory('cost')->where('konto', '=', $cos)->order_by($order, 'ASC')->find_all();
                    } else {
                        $this->template->koszty = ORM::factory('cost')->where('konto', '=', $cos)->order_by($order, 'DESC')->find_all();
                    }
                }
            }
        }
    }

    public function action_fkoszty() {
        $this->template->content = 'fkoszty'; //załadowanie widoku
        $auth = Auth::instance(); //utworzenie instancji obiektu Auth
        $this->template->jest = 'http://f4s.on-depot.de/' . $this->request->uri();
        if (!$auth->logged_in('login')) { //sprawdzanie czy użytkownik jest zalogowany
            $this->request->redirect('user/login'); //jeśli nie, przekierowujemy do logowania
        } else {


            if ($_POST['id'] && !$_POST['fak']) {
                foreach ($_POST['id'] as $id) {
                    $usun = ORM::factory('cost')->where('id', '=', $id)->find();
                    $usun->delete();
                }
                $this->template->koszty = ORM::factory('cost')->where('konto', '=', $cos)->order_by('data', 'DESC')->find_all();
            }

            $this->session = Session::instance();
            $cos = $this->session->get('konto');

            $a = 2;
            $this->template->koszty = ORM::factory('cost')->where('konto', '=', $cos)->order_by('data', 'DESC')->find_all();


            if ($_POST['fak']) {

                require_once(APPPATH . '/faktury/fakturakoszty.php');
                require_once(APPPATH . '/faktury/wewnetrzna.php');

                function EmptyDir($dirName, $rmDir = false) {
                    if ($dirHandle = opendir($dirName)) {
                        while (false !== ($dirFile = readdir($dirHandle)))
                            if ($dirFile != "." && $dirFile != "..")
                                if (!unlink($dirName . "/" . $dirFile))
                                    return false;
                        closedir($dirHandle);
                        if ($rmDir)
                            if (!rmdir($dirName))
                                return false;
                        return true;
                    }
                    else
                        return false;
                }

                $folder = date('Y-m-d') . '-kosz';

                $i = 1;
                while (file_exists(APPPATH . '/faktury/faktury/' . $folder . $i))
                    $i++;
                $fol = $folder . $i;

                mkdir(APPPATH . '/faktury/faktury/' . $fol, 0777, true);
                chmod(APPPATH . '/faktury/faktury/' . $fol, 0777);

                if ($_POST['id'] && $_POST['command'] != 'delete') {
                    foreach ($_POST['id'] as $id) {

                        $firma = ORM::factory('cost')->where('id', '=', $id)->find();
                        $num = ORM::factory('firm')->where('id', '=', $cos)->find();

                        $this->SES = Session::instance();
                        $sesjakonto = (int) $this->SES->get('konto');



                        $ide = ORM::factory('cost')->where('id', '=', $id)->find();

                        $numerfaktury = str_replace('/', '-', $ide->nr);
                        $walet = $ide->waluta;

                        $nie = false;
                        if ($ide->n_kraj == 'Polska' && $ide->s_kraj != 'Polska')
                            $nie = true;


                        $dane = new danefakturykoszty();
                        $dane->art_name = $ide->sprzedaz;
                        $dane->ilosc = $ide->ilosc;
                        $dane->price = $ide->cena;
                        $dane->data = $ide->data;
                        $dane->data_s = $ide->data_sprzedazy;
                        $dane->waluta = $ide->waluta;
                        $dane->vat = $ide->vat;
                        $datki = explode('-', $ide->data);
                        $danefakt[] = $dane;

                        if (date('w', mktime(0, 0, 0, $datki[1], $datki[2], $datki[0])) == 1) {

                            $datae = date('Y-m-d', mktime(0, 0, 0, $datki[1], $datki[2] - 3, $datki[0]));
                            if ($walet == 'EUR')
                                $kurs = ORM::factory('kur')->where('data', '=', $datae)->find();
                            else
                                $kurs = ORM::factory('kure')->where('data', '=', $datae)->find();
                        } else {
                            $datae = date('Y-m-d', mktime(0, 0, 0, $datki[1], $datki[2] - 1, $datki[0]));
                            if ($walet == 'EUR')
                                $kurs = ORM::factory('kur')->where('data', '=', $datae)->find();
                            else
                                $kurs = ORM::factory('kure')->where('data', '=', $datae)->find();
                            if ($kurs->id == '') {
                                $cofanie = 2;
                                while ($kurs->id == '') {
                                    $datae = date('Y-m-d', mktime(0, 0, 0, $datki[1], $datki[2] - $cofanie, $datki[0]));
                                    if ($walet == 'EUR')
                                        $kurs = ORM::factory('kur')->where('data', '=', $datae)->find();
                                    else
                                        $kurs = ORM::factory('kure')->where('data', '=', $datae)->find();

                                    $cofanie++;
                                }
                            }
                        }



                        if (!empty($dane) && $nie)
                            wewnetrzna($numerfaktury, $firma, $dane, $kurs, $num->do_faktury, $_POST['wystawienie'], $_POST['platnosc'], $fol);

                        if ($_POST['jezyk'] == 1) {
                            if (!empty($dane))
                                faktura($numerfaktury, $firma, $dane, $kurs, $num->do_faktury, $_POST['wystawienie'], $_POST['platnosc'], $fol);
                        }
                        else if ($_POST['jezyk'] == 2) {
                            if (!empty($dane))
                                if ($walet == 'EUR')
                                    faktura1($numerfaktury, $firma, $dane, $kurs, $num->do_faktury, $_POST['wystawienie'], $_POST['platnosc'], $fol);
                                else
                                    faktura2($numerfaktury, $firma, $dane, $kurs, $num->do_faktury, $_POST['wystawienie'], $_POST['platnosc'], $fol);
                        }
                        else {
                            if ($ide->jezyk == 1) {
                                if (!empty($dane))
                                    faktura($numerfaktury, $firma, $dane, $kurs, $num->do_faktury, $_POST['wystawienie'], $_POST['platnosc'], $fol);
                            }
                            else {
                                if (!empty($dane))
                                    faktura1($numerfaktury, $firma, $dane, $kurs, $num->do_faktury, $_POST['wystawienie'], $_POST['platnosc'], $fol);
                            }
                        }
                    }

                    $zip = new ZipArchive();
                    $filename = APPPATH . 'faktury/faktury/' . $fol . '.zip';

                    if (file_exists(APPPATH . 'faktury/faktury/' . $fol)) {

                        if ($zip->open($filename, ZIPARCHIVE::CREATE) !== TRUE) {
                            exit("cannot open <$filename>\n");
                        }

                        foreach (new DirectoryIterator(APPPATH . 'faktury/faktury/' . $fol) as $file)
                            if (!$file->isDot()) {
                                $zip->addFile(APPPATH . 'faktury/faktury/' . $fol . '/' . $file->getFilename(), $file->getFilename());
                            }
                        $zip->close();

                        $this->response->send_file(APPPATH . '/faktury/faktury/' . $fol . '.zip');
                    }
                }
            } else {
                if ($_POST['order']) {
                    $order = $this->session->get('order');
                    $a = $this->session->get('a');
                    if ($order == $_POST['order'] && $a != 1) {
                        $a = 1;
                        $this->session->set('a', $a);
                    } else if ($order == $_POST['order'] && $a == 1) {
                        $a = 2;
                        $this->session->set('a', $a);
                    }
                    $order = $_POST['order'];
                    $this->session->set('order', $order);
                    if ($a != 1) {
                        $this->template->koszty = ORM::factory('cost')->where('konto', '=', $cos)->order_by($order, 'ASC')->find_all();
                    } else {
                        $this->template->koszty = ORM::factory('cost')->where('konto', '=', $cos)->order_by($order, 'DESC')->find_all();
                    }
                }
            }
        }
    }

    public function action_firmy() {
        $this->template->content = 'lfirmy'; //załadowanie widoku
        $auth = Auth::instance(); //utworzenie instancji obiektu Auth
        $this->template->jest = 'http://f4s.on-depot.de/' . $this->request->uri();
        if (!$auth->logged_in('login')) { //sprawdzanie czy użytkownik jest zalogowany
            $this->request->redirect('user/login'); //jeśli nie, przekierowujemy do logowania
        } else {

            $this->session = Session::instance();
            $cos = $this->session->get('konto');
            $a = 2;

            $this->template->firmy = ORM::factory('firm')->order_by('nazwa', 'ASC')->find_all();

            if ($_POST) {
                if ($_POST['id']) {
                    foreach ($_POST['id'] as $id) {
                        $usun = ORM::factory('firm')->where('id', '=', $id)->find();
                        $usun->delete();
                    }
                    $this->template->firmy = ORM::factory('firm')->order_by('nazwa', 'ASC')->find_all();
                } else {
                    $order = $this->session->get('order');
                    $a = $this->session->get('a');
                    if ($order == $_POST['order'] && $a != 1) {
                        $a = 1;
                        $this->session->set('a', $a);
                    } else if ($order == $_POST['order'] && $a == 1) {
                        $a = 2;
                        $this->session->set('a', $a);
                    }
                    $order = $_POST['order'];
                    $this->session->set('order', $order);
                    if ($a != 1) {
                        $this->template->firmy = ORM::factory('firm')->order_by($order, 'ASC')->find_all();
                    } else {
                        $this->template->firmy = ORM::factory('firm')->order_by($order, 'DESC')->find_all();
                    }
                }
            }
        }
    }

    public function action_konta() {
        $this->template->content = 'lkonta'; //załadowanie widoku
        $auth = Auth::instance(); //utworzenie instancji obiektu Auth
        $this->template->jest = 'http://f4s.on-depot.de/' . $this->request->uri();
        if (!$auth->logged_in('login')) { //sprawdzanie czy użytkownik jest zalogowany
            $this->request->redirect('user/login'); //jeśli nie, przekierowujemy do logowania
        } else {

            $this->session = Session::instance();
            $cos = $this->session->get('konto');

            $this->template->konta = ORM::factory('ebayconfig')->where('firma', '=', $cos)->order_by('name', 'ASC')->find_all();

            if ($_POST) {
                if ($_POST['id']) {
                    foreach ($_POST['id'] as $id) {
                        $usun = ORM::factory('ebayconfig')->where('id', '=', $id)->find();
                        $usun->delete();
                    }
                    $this->template->konta = ORM::factory('ebayconfig')->where('firma', '=', $cos)->order_by('name', 'ASC')->find_all();
                } else {
                    $order = $this->session->get('order');
                    $a = $this->session->get('a');
                    if ($order == $_POST['order'] && $a != 1) {
                        $a = 1;
                        $this->session->set('a', $a);
                    } else if ($order == $_POST['order'] && $a == 1) {
                        $a = 2;
                        $this->session->set('a', $a);
                    }
                    $order = $_POST['order'];
                    $this->session->set('order', $order);
                    if ($a != 1) {
                        $this->template->konta = ORM::factory('ebayconfig')->where('firma', '=', $cos)->order_by($order, 'ASC')->find_all();
                    } else {
                        $this->template->konta = ORM::factory('ebayconfig')->where('firma', '=', $cos)->order_by($order, 'DESC')->find_all();
                    }
                }
            }
        }
    }

    public function action_sprzedaz() {
        $this->template->content = 'lsprzedaz'; //załadowanie widoku
        $auth = Auth::instance(); //utworzenie instancji obiektu Auth
        $this->template->jest = 'http://f4s.on-depot.de/' . $this->request->uri();
        if (!$auth->logged_in('login')) { //sprawdzanie czy użytkownik jest zalogowany
            $this->request->redirect('user/login'); //jeśli nie, przekierowujemy do logowania
        } else {

            $produkt = array();
            $data_sprzedazy = array();
            $nabywca = array();
            $nr_faktury = array();
            $nrfak = array();
            $art_id = array();


            $this->session = Session::instance();
            $cos = $this->session->get('konto');

            $a = 2;
            if (!$_GET['order']) {
                $this->session->delete('data_sprzedazy');
                $this->session->delete('nabywca');
                $this->session->delete('nr_faktury');
                $this->session->delete('nrfak');
                $this->session->delete('art_id');
                $this->session->delete('produkt');
                $this->session->delete('transaction_id');
            }
            // $this->template->sprzedaz = ORM::factory('sell')->select(array('*','GROUP_CONCAT(  art_id SEPARATOR  "+" )'))->where('konto', '=', $cos)->group_by('art_id')->order_by('data_sprzedazy', 'ASC')->find_all();
            //$this->template->sprzedaz = DB::select(array('*','GROUP_CONCAT(art_id SEPARATOR \'+\') AS `art_id`'))->from("sells")->where('konto', '=', $cos)->group_by('numer_transakcji')->order_by('data_sprzedazy', 'ASC')->as_object()->execute();
            //$this->template->sprzedaz =  DB::select('id`,`nr_faktury`,`nabywca`, `produkt`, `numer_transakcji`, `zrodlo_transakcji`, `ilosc`, `cena_jednostkowa`, `wartosc`, `waluta`, `data_sprzedazy`, `nrfak`, `wysylka`, GROUP_CONCAT(art_id SEPARATOR \'<br />\') AS `art_id')->from("sells")->where('konto', '=', $cos)->group_by('numer_transakcji')->order_by('data_sprzedazy', 'ASC')->as_object()->execute();

            if ($this->session->get('transiki') == 1)
                $this->template->sprzedaz = DB::select('id`,`nr_faktury`,`nabywca`, `produkt`, `numer_transakcji`, `zrodlo_transakcji`, `ilosc`, `cena_jednostkowa`, `wartosc`, `waluta`, `data_sprzedazy`, `nrfak`, `wysylka`, GROUP_CONCAT(art_id SEPARATOR \'<br />\') AS `art_id')->from("sells")->where('konto', '=', $cos)->group_by('numer_transakcji')->group_by('data_sprzedazy')->group_by('nabywca')->order_by('data_sprzedazy', 'ASC')->as_object()->execute();
            else
                $this->template->sprzedaz = DB::select('id`,`nr_faktury`,`nabywca`, `produkt`, `numer_transakcji`, `zrodlo_transakcji`, `ilosc`, `cena_jednostkowa`, `wartosc`, `waluta`, `data_sprzedazy`, `nrfak`, `wysylka`, GROUP_CONCAT(art_id SEPARATOR \'<br />\') AS `art_id')->from("sells")->where('konto', '=', $cos)->and_where('numer_transakcji', '!=', '')->group_by('numer_transakcji')->group_by('data_sprzedazy')->group_by('nabywca')->order_by('data_sprzedazy', 'ASC')->as_object()->execute();

            if ($_GET && !isset($_GET['transy'])) {
                if ($_GET['id']) {
                    foreach ($_GET['id'] as $id) {
                        $usun = ORM::factory('sell')->where('id', '=', $id)->find();
                        $usun->delete();
                    }

                    if ($this->session->get('transiki') == 1)
                        $this->template->sprzedaz = DB::select('id`,`nr_faktury`,`nabywca`, `produkt`, `numer_transakcji`, `zrodlo_transakcji`, `ilosc`, `cena_jednostkowa`, `wartosc`, `waluta`, `data_sprzedazy`, `nrfak`, `wysylka`, GROUP_CONCAT(art_id SEPARATOR \'<br />\') AS `art_id')->from("sells")->where('konto', '=', $cos)->group_by('numer_transakcji')->group_by('data_sprzedazy')->group_by('nabywca')->order_by('data_sprzedazy', 'ASC')->as_object()->execute();
                    else
                        $this->template->sprzedaz = DB::select('id`,`nr_faktury`,`nabywca`, `produkt`, `numer_transakcji`, `zrodlo_transakcji`, `ilosc`, `cena_jednostkowa`, `wartosc`, `waluta`, `data_sprzedazy`, `nrfak`, `wysylka`, GROUP_CONCAT(art_id SEPARATOR \'<br />\') AS `art_id')->from("sells")->where('konto', '=', $cos)->and_where('numer_transakcji', '!=', '')->group_by('numer_transakcji')->group_by('data_sprzedazy')->group_by('nabywca')->order_by('data_sprzedazy', 'ASC')->as_object()->execute();
                }
                if ($_GET['order']) {
                    $order = $this->session->get('order');
                    $a = $this->session->get('a');
                    if ($order == $_GET['order'] && $a != 1) {
                        $a = 1;
                        $this->session->set('a', $a);
                    } else if ($order == $_GET['order'] && $a == 1) {
                        $a = 2;
                        $this->session->set('a', $a);
                    }
                    $order = $_GET['order'];
                    $this->session->set('order', $order);
                    $data_sprzedazy = $this->session->get('data_sprzedazy');
                    $nabywca = $this->session->get('nabywca');
                    $nr_faktury = $this->session->get('nr_faktury');
                    $nrfak = $this->session->get('nrfak');
                    $art_id = $this->session->get('art_id');
                    $produkt = $this->session->get('produkt');
                    $transaction_id = $this->session->get('transaction_id');
                    if ($a != 1) {

                        if ($order != 'numer') {
                            if ($this->session->get('transiki') == 1)
                                $costam = DB::select('id`,`nr_faktury`,`nabywca`, `produkt`, `numer_transakcji`, `zrodlo_transakcji`, `ilosc`, `cena_jednostkowa`, `wartosc`, `waluta`, `data_sprzedazy`, `nrfak`, `wysylka`, GROUP_CONCAT(art_id SEPARATOR \'<br />\') AS `art_id')->from("sells")->where('konto', '=', $cos)->group_by('numer_transakcji')->group_by('data_sprzedazy')->group_by('nabywca')->order_by($order, 'ASC');
                            else
                                $costam = DB::select('id`,`nr_faktury`,`nabywca`, `produkt`, `numer_transakcji`, `zrodlo_transakcji`, `ilosc`, `cena_jednostkowa`, `wartosc`, `waluta`, `data_sprzedazy`, `nrfak`, `wysylka`, GROUP_CONCAT(art_id SEPARATOR \'<br />\') AS `art_id')->from("sells")->where('konto', '=', $cos)->and_where('numer_transakcji', '!=', '')->group_by('numer_transakcji')->group_by('data_sprzedazy')->group_by('nabywca')->order_by($order, 'ASC');
                            if ($data_sprzedazy)
                                foreach ($data_sprzedazy as $d)
                                    $costam->and_where('data_sprzedazy', '=', $d);
                            if ($nabywca)
                                foreach ($nabywca as $d)
                                    $costam->and_where('nabywca', '=', $d);
                            if ($nr_faktury)
                                foreach ($nr_faktury as $d)
                                    $costam->and_where('nr_faktury', '=', $d);
                            if ($nrfak)
                                foreach ($nrfak as $d)
                                    $costam->and_where('nrfak', '=', $d);
                            if ($art_id)
                                foreach ($art_id as $d)
                                    $costam->and_where('art_id', '=', $d);
                            if ($produkt)
                                foreach ($produkt as $d)
                                    $costam->and_where('produkt', '=', $d);
                            $this->template->sprzedaz = $costam->as_object()->execute();
                        }
                        else {
                            if ($this->session->get('transiki') == 1)
                                $costam = DB::select('id`,`nr_faktury`,`nabywca`, `produkt`, `numer_transakcji`, `zrodlo_transakcji`, `ilosc`, `cena_jednostkowa`, `wartosc`, `waluta`, `data_sprzedazy`, `nrfak`, `wysylka`, GROUP_CONCAT(art_id SEPARATOR \'<br />\') AS `art_id')->from("sells")->where('konto', '=', $cos)->group_by('numer_transakcji')->group_by('data_sprzedazy')->group_by('nabywca')->order_by('data', 'ASC');
                            else
                                $costam = DB::select('id`,`nr_faktury`,`nabywca`, `produkt`, `numer_transakcji`, `zrodlo_transakcji`, `ilosc`, `cena_jednostkowa`, `wartosc`, `waluta`, `data_sprzedazy`, `nrfak`, `wysylka`, GROUP_CONCAT(art_id SEPARATOR \'<br />\') AS `art_id')->from("sells")->where('konto', '=', $cos)->and_where('numer_transakcji', '!=', '')->group_by('numer_transakcji')->group_by('data_sprzedazy')->group_by('nabywca')->order_by('data', 'ASC');
                            if ($data_sprzedazy)
                                foreach ($data_sprzedazy as $d)
                                    $costam->and_where('data_sprzedazy', '=', $d);
                            if ($nabywca)
                                foreach ($nabywca as $d)
                                    $costam->and_where('nabywca', '=', $d);
                            if ($nr_faktury)
                                foreach ($nr_faktury as $d)
                                    $costam->and_where('nr_faktury', '=', $d);
                            if ($nrfak)
                                foreach ($nrfak as $d)
                                    $costam->and_where('nrfak', '=', $d);
                            if ($art_id)
                                foreach ($art_id as $d)
                                    $costam->and_where('art_id', '=', $d);
                            if ($produkt)
                                foreach ($produkt as $d)
                                    $costam->and_where('produkt', '=', $d);
                            $this->template->sprzedaz = $costam->as_object()->execute();
                        }
                    }
                    else {
                        if ($order != 'numer') {
                            if ($this->session->get('transiki') == 1)
                                $costam = DB::select('id`,`nr_faktury`,`nabywca`, `produkt`, `numer_transakcji`, `zrodlo_transakcji`, `ilosc`, `cena_jednostkowa`, `wartosc`, `waluta`, `data_sprzedazy`, `nrfak`, `wysylka`, GROUP_CONCAT(art_id SEPARATOR \'<br />\') AS `art_id')->from("sells")->where('konto', '=', $cos)->group_by('numer_transakcji')->group_by('data_sprzedazy')->group_by('nabywca')->order_by($order, 'DESC');
                            else
                                $costam = DB::select('id`,`nr_faktury`,`nabywca`, `produkt`, `numer_transakcji`, `zrodlo_transakcji`, `ilosc`, `cena_jednostkowa`, `wartosc`, `waluta`, `data_sprzedazy`, `nrfak`, `wysylka`, GROUP_CONCAT(art_id SEPARATOR \'<br />\') AS `art_id')->from("sells")->where('konto', '=', $cos)->and_where('numer_transakcji', '!=', '')->group_by('numer_transakcji')->group_by('data_sprzedazy')->group_by('nabywca')->order_by($order, 'DESC');
                            if ($data_sprzedazy)
                                foreach ($data_sprzedazy as $d)
                                    $costam->and_where('data_sprzedazy', '=', $d);
                            if ($nabywca)
                                foreach ($nabywca as $d)
                                    $costam->and_where('nabywca', '=', $d);
                            if ($nr_faktury)
                                foreach ($nr_faktury as $d)
                                    $costam->and_where('nr_faktury', '=', $d);
                            if ($nrfak)
                                foreach ($nrfak as $d)
                                    $costam->and_where('nrfak', '=', $d);
                            if ($art_id)
                                foreach ($art_id as $d)
                                    $costam->and_where('art_id', '=', $d);
                            if ($produkt)
                                foreach ($produkt as $d)
                                    $costam->and_where('produkt', '=', $d);
                            $this->template->sprzedaz = $costam->as_object()->execute();
                        }
                        else {
                            if ($this->session->get('transiki') == 1)
                                $costam = DB::select('id`,`nr_faktury`,`nabywca`, `produkt`, `numer_transakcji`, `zrodlo_transakcji`, `ilosc`, `cena_jednostkowa`, `wartosc`, `waluta`, `data_sprzedazy`, `nrfak`, `wysylka`, GROUP_CONCAT(art_id SEPARATOR \'<br />\') AS `art_id')->from("sells")->where('konto', '=', $cos)->group_by('numer_transakcji')->group_by('data_sprzedazy')->group_by('nabywca')->order_by('data', 'DESC')->order_by('nr_faktury', 'DESC');
                            else
                                $costam = DB::select('id`,`nr_faktury`,`nabywca`, `produkt`, `numer_transakcji`, `zrodlo_transakcji`, `ilosc`, `cena_jednostkowa`, `wartosc`, `waluta`, `data_sprzedazy`, `nrfak`, `wysylka`, GROUP_CONCAT(art_id SEPARATOR \'<br />\') AS `art_id')->from("sells")->where('konto', '=', $cos)->and_where('numer_transakcji', '!=', '')->group_by('numer_transakcji')->group_by('data_sprzedazy')->group_by('nabywca')->order_by('data', 'DESC')->order_by('nr_faktury', 'DESC');
                            if ($data_sprzedazy)
                                foreach ($data_sprzedazy as $d)
                                    $costam->and_where('data_sprzedazy', '=', $d);
                            if ($nabywca)
                                foreach ($nabywca as $d)
                                    $costam->and_where('nabywca', '=', $d);
                            if ($nr_faktury)
                                foreach ($nr_faktury as $d)
                                    $costam->and_where('nr_faktury', '=', $d);
                            if ($nrfak)
                                foreach ($nrfak as $d)
                                    $costam->and_where('nrfak', '=', $d);
                            if ($art_id)
                                foreach ($art_id as $d)
                                    $costam->and_where('art_id', '=', $d);
                            if ($produkt)
                                foreach ($produkt as $d)
                                    $costam->and_where('produkt', '=', $d);
                            $this->template->sprzedaz = $costam->as_object()->execute();
                        }
                    }
                }
                if ($_GET['data_sprzedazy']) {
                    $data_sprzedazy = $this->session->get('data_sprzedazy');
                    $data_sprzedazy[] = $_GET['data_sprzedazy'];
                    $this->session->set('data_sprzedazy', $data_sprzedazy);

                    $data_sprzedazy = $this->session->get('data_sprzedazy');
                    $nabywca = $this->session->get('nabywca');
                    $nr_faktury = $this->session->get('nr_faktury');
                    $nrfak = $this->session->get('nrfak');
                    $art_id = $this->session->get('art_id');
                    $produkt = $this->session->get('produkt');

                    if ($this->session->get('transiki') == 1)
                        $costam = DB::select('id`,`nr_faktury`,`nabywca`, `produkt`, `numer_transakcji`, `zrodlo_transakcji`, `ilosc`, `cena_jednostkowa`, `wartosc`, `waluta`, `data_sprzedazy`, `nrfak`, `wysylka`, GROUP_CONCAT(art_id SEPARATOR \'<br />\') AS `art_id')->from("sells")->where('konto', '=', $cos)->group_by('numer_transakcji')->group_by('data_sprzedazy')->group_by('nabywca');
                    else
                        $costam = DB::select('id`,`nr_faktury`,`nabywca`, `produkt`, `numer_transakcji`, `zrodlo_transakcji`, `ilosc`, `cena_jednostkowa`, `wartosc`, `waluta`, `data_sprzedazy`, `nrfak`, `wysylka`, GROUP_CONCAT(art_id SEPARATOR \'<br />\') AS `art_id')->from("sells")->where('konto', '=', $cos)->and_where('numer_transakcji', '!=', '')->group_by('numer_transakcji')->group_by('data_sprzedazy')->group_by('nabywca');
                    if ($data_sprzedazy)
                        foreach ($data_sprzedazy as $d)
                            $costam->and_where('data_sprzedazy', '=', $d);
                    if ($nabywca)
                        foreach ($nabywca as $d)
                            $costam->and_where('nabywca', '=', $d);
                    if ($nr_faktury)
                        foreach ($nr_faktury as $d)
                            $costam->and_where('nr_faktury', '=', $d);
                    if ($nrfak)
                        foreach ($nrfak as $d)
                            $costam->and_where('nrfak', '=', $d);
                    if ($art_id)
                        foreach ($art_id as $d)
                            $costam->and_where('art_id', '=', $d);
                    if ($produkt)
                        foreach ($produkt as $d)
                            $costam->and_where('produkt', '=', $d);
                    $this->template->sprzedaz = $costam->as_object()->execute();
                }
                if ($_GET['transaction_id']) {
                    $transaction_id[] = $_GET['transaction_id'];
                    $this->session->set('transaction_id', $transaction_id);

                    if ($this->session->get('transiki') == 1)
                        $costam = DB::select('id`,`nr_faktury`,`nabywca`, `produkt`, `numer_transakcji`, `zrodlo_transakcji`, `ilosc`, `cena_jednostkowa`, `wartosc`, `waluta`, `data_sprzedazy`, `nrfak`, `wysylka`, GROUP_CONCAT(art_id SEPARATOR \'<br />\') AS `art_id')->from("sells")->where('konto', '=', $cos)->group_by('numer_transakcji')->group_by('data_sprzedazy')->group_by('nabywca');
                    else
                        $costam = DB::select('id`,`nr_faktury`,`nabywca`, `produkt`, `numer_transakcji`, `zrodlo_transakcji`, `ilosc`, `cena_jednostkowa`, `wartosc`, `waluta`, `data_sprzedazy`, `nrfak`, `wysylka`, GROUP_CONCAT(art_id SEPARATOR \'<br />\') AS `art_id')->from("sells")->where('konto', '=', $cos)->and_where('numer_transakcji', '!=', '')->group_by('numer_transakcji')->group_by('data_sprzedazy')->group_by('nabywca');
                    if ($transaction_id)
                        foreach ($transaction_id as $d)
                            $costam->and_where('numer_transakcji', '=', $d);

                    $this->template->sprzedaz = $costam->as_object()->execute();
                }
                if ($_GET['nabywca']) {
                    $nabywca = $this->session->get('nabywca');
                    $nabywca[] = $_GET['nabywca'];
                    $this->session->set('nabywca', $nabywca);


                    if ($this->session->get('transiki') == 1)
                        $costam = DB::select('id`,`nr_faktury`,`nabywca`, `produkt`, `numer_transakcji`, `zrodlo_transakcji`, `ilosc`, `cena_jednostkowa`, `wartosc`, `waluta`, `data_sprzedazy`, `nrfak`, `wysylka`, GROUP_CONCAT(art_id SEPARATOR \'<br />\') AS `art_id')->from("sells")->where('konto', '=', $cos)->group_by('numer_transakcji')->group_by('data_sprzedazy')->group_by('nabywca');
                    else
                        $costam = DB::select('id`,`nr_faktury`,`nabywca`, `produkt`, `numer_transakcji`, `zrodlo_transakcji`, `ilosc`, `cena_jednostkowa`, `wartosc`, `waluta`, `data_sprzedazy`, `nrfak`, `wysylka`, GROUP_CONCAT(art_id SEPARATOR \'<br />\') AS `art_id')->from("sells")->where('konto', '=', $cos)->and_where('numer_transakcji', '!=', '')->group_by('numer_transakcji')->group_by('data_sprzedazy')->group_by('nabywca');
                    if ($nabywca)
                        foreach ($nabywca as $d)
                            $costam->and_where('nabywca', '=', $d);;

                    $this->template->sprzedaz = $costam->as_object()->execute();
                }
                if ($_GET['nr_faktury']) {
                    $nr_faktury = $this->session->get('nr_faktury');
                    $nr_faktury[] = $_GET['nr_faktury'];
                    $this->session->set('nr_faktury', $nr_faktury);


                    if ($this->session->get('transiki') == 1)
                        $costam = DB::select('id`,`nr_faktury`,`nabywca`, `produkt`, `numer_transakcji`, `zrodlo_transakcji`, `ilosc`, `cena_jednostkowa`, `wartosc`, `waluta`, `data_sprzedazy`, `nrfak`, `wysylka`, GROUP_CONCAT(art_id SEPARATOR \'<br />\') AS `art_id')->from("sells")->where('konto', '=', $cos)->group_by('numer_transakcji')->group_by('data_sprzedazy')->group_by('nabywca');
                    else
                        $costam = DB::select('id`,`nr_faktury`,`nabywca`, `produkt`, `numer_transakcji`, `zrodlo_transakcji`, `ilosc`, `cena_jednostkowa`, `wartosc`, `waluta`, `data_sprzedazy`, `nrfak`, `wysylka`, GROUP_CONCAT(art_id SEPARATOR \'<br />\') AS `art_id')->from("sells")->where('konto', '=', $cos)->and_where('numer_transakcji', '!=', '')->group_by('numer_transakcji')->group_by('data_sprzedazy')->group_by('nabywca');

                    if ($nr_faktury)
                        foreach ($nr_faktury as $d)
                            $costam->and_where('nr_faktury', '=', $d);

                    $this->template->sprzedaz = $costam->as_object()->execute();
                }
                if ($_GET['nrfak']) {
                    $nrfak = $this->session->get('nrfak');
                    $nrfak[] = $_GET['nrfak'];
                    $this->session->set('nrfak', $nrfak);



                    if ($this->session->get('transiki') == 1)
                        $costam = DB::select('id`,`nr_faktury`,`nabywca`, `produkt`, `numer_transakcji`, `zrodlo_transakcji`, `ilosc`, `cena_jednostkowa`, `wartosc`, `waluta`, `data_sprzedazy`, `nrfak`, `wysylka`, GROUP_CONCAT(art_id SEPARATOR \'<br />\') AS `art_id')->from("sells")->where('konto', '=', $cos)->group_by('numer_transakcji')->group_by('data_sprzedazy')->group_by('nabywca');
                    else
                        $costam = DB::select('id`,`nr_faktury`,`nabywca`, `produkt`, `numer_transakcji`, `zrodlo_transakcji`, `ilosc`, `cena_jednostkowa`, `wartosc`, `waluta`, `data_sprzedazy`, `nrfak`, `wysylka`, GROUP_CONCAT(art_id SEPARATOR \'<br />\') AS `art_id')->from("sells")->where('konto', '=', $cos)->and_where('numer_transakcji', '!=', '')->group_by('numer_transakcji')->group_by('data_sprzedazy')->group_by('nabywca');
                    if ($nrfak)
                        foreach ($nrfak as $d)
                            $costam->and_where('nrfak', '=', $d);

                    $this->template->sprzedaz = $costam->as_object()->execute();
                }
                if ($_GET['art_id']) {
                    $art_id = $this->session->get('art_id');
                    $art_id[] = $_GET['art_id'];
                    $this->session->set('art_id', $art_id);

                    if ($this->session->get('transiki') == 1)
                        $costam = DB::select('id`,`nr_faktury`,`nabywca`, `produkt`, `numer_transakcji`, `zrodlo_transakcji`, `ilosc`, `cena_jednostkowa`, `wartosc`, `waluta`, `data_sprzedazy`, `nrfak`, `wysylka`, GROUP_CONCAT(art_id SEPARATOR \'<br />\') AS `art_id')->from("sells")->where('konto', '=', $cos)->group_by('numer_transakcji')->group_by('data_sprzedazy')->group_by('nabywca');
                    else
                        $costam = DB::select('id`,`nr_faktury`,`nabywca`, `produkt`, `numer_transakcji`, `zrodlo_transakcji`, `ilosc`, `cena_jednostkowa`, `wartosc`, `waluta`, `data_sprzedazy`, `nrfak`, `wysylka`, GROUP_CONCAT(art_id SEPARATOR \'<br />\') AS `art_id')->from("sells")->where('konto', '=', $cos)->and_where('numer_transakcji', '!=', '')->group_by('numer_transakcji')->group_by('data_sprzedazy')->group_by('nabywca');
                    if ($art_id)
                        foreach ($art_id as $d)
                            $costam->and_where('art_id', '=', $d);

                    $this->template->sprzedaz = $costam->as_object()->execute();
                }
                if ($_GET['produkt']) {
                    $produkt = $this->session->get('produkt');
                    $produkt[] = $_GET['produkt'];
                    $this->session->set('produkt', $produkt);

                    if ($this->session->get('transiki') == 1)
                        $costam = DB::select('id`,`nr_faktury`,`nabywca`, `produkt`, `numer_transakcji`, `zrodlo_transakcji`, `ilosc`, `cena_jednostkowa`, `wartosc`, `waluta`, `data_sprzedazy`, `nrfak`, `wysylka`, GROUP_CONCAT(art_id SEPARATOR \'<br />\') AS `art_id')->from("sells")->where('konto', '=', $cos)->group_by('numer_transakcji')->group_by('data_sprzedazy')->group_by('nabywca');
                    else
                        $costam = DB::select('id`,`nr_faktury`,`nabywca`, `produkt`, `numer_transakcji`, `zrodlo_transakcji`, `ilosc`, `cena_jednostkowa`, `wartosc`, `waluta`, `data_sprzedazy`, `nrfak`, `wysylka`, GROUP_CONCAT(art_id SEPARATOR \'<br />\') AS `art_id')->from("sells")->where('konto', '=', $cos)->and_where('numer_transakcji', '!=', '')->group_by('numer_transakcji')->group_by('data_sprzedazy')->group_by('nabywca');

                    if ($produkt)
                        foreach ($produkt as $d)
                            $costam->and_where('produkt', '=', $d);
                    $this->template->sprzedaz = $costam->as_object()->execute();
                }
            }
        }
    }

    public function action_faktury() {
        $this->template->content = 'lfaktury'; //załadowanie widoku
        $auth = Auth::instance(); //utworzenie instancji obiektu Auth
        $this->template->jest = 'http://f4s.on-depot.de/' . $this->request->uri();
        if (!$auth->logged_in('login')) { //sprawdzanie czy użytkownik jest zalogowany
            $this->request->redirect('user/login'); //jeśli nie, przekierowujemy do logowania
        } else {

            $dane = array();
            $this->session = Session::instance();
            $cos = $this->session->get('konto');
            $a = 2;


            $faktury = ORM::factory('sell')->where('konto', '=', $cos)->group_by('nr_faktury')->order_by('data', 'ASC')->find_all();
            if ($faktury)
                foreach ($faktury as $fak) {
                    $tem = new takietam();
                    $tem->faktura = $fak;
                    if ($this->session->get('transiki') == 1)
                        $wiecej = DB::select('id`,`nr_faktury`,`nabywca`, `produkt`, `numer_transakcji`, `zrodlo_transakcji`, `ilosc`, `cena_jednostkowa`, `wartosc`, `waluta`, `data_sprzedazy`, `nrfak`, `wysylka`, GROUP_CONCAT(art_id SEPARATOR \'<br />\') AS `art_id')->from("sells")->where('nr_faktury', '=', $fak->nr_faktury)->and_where('konto', '=', $cos)->group_by('numer_transakcji')->group_by('data_sprzedazy')->group_by('nabywca')->order_by('data', 'ASC')->as_object()->execute();
                    else
                        $wiecej = DB::select('id`,`nr_faktury`,`nabywca`, `produkt`, `numer_transakcji`, `zrodlo_transakcji`, `ilosc`, `cena_jednostkowa`, `wartosc`, `waluta`, `data_sprzedazy`, `nrfak`, `wysylka`, GROUP_CONCAT(art_id SEPARATOR \'<br />\') AS `art_id')->from("sells")->where('nr_faktury', '=', $fak->nr_faktury)->and_where('numer_transakcji', '!=', '')->and_where('konto', '=', $cos)->group_by('numer_transakcji')->group_by('data_sprzedazy')->group_by('nabywca')->order_by('data', 'ASC')->as_object()->execute();
                    $tem->wiecej = $wiecej;
                    $firma = ORM::factory('firm')->where('id', '=', $cos)->find();
                    $tem->zfirmy = $firma->do_faktury;
                    $fak = ORM::factory('fakture')->where('nr_faktury', '=', $fak->nr_faktury)->and_where('konto', '=', $cos)->find();
                    $tem->vat = $fak->vat;
                    $dane[] = $tem;
                }

            $this->template->faktury = $dane;

            if ($_POST) {
                if (!$_POST['fak'] && !isset($_POST['transy'])) {
                    if ($_POST['id']) {
                        foreach ($_POST['id'] as $id) {
                            $ide = ORM::factory('sell')->where('id', '=', $id)->find();

                            $selsy = ORM::factory('sell')->where('nr_faktury', '=', $ide->nr_faktury)->and_where('konto', '=', $cos)->find_all();
                            $faktureczka = ORM::factory('fakture')->where('nr_faktury', '=', $ide->nr_faktury)->and_where('konto', '=', $cos)->find();
                            if ($faktureczka->id != '')
                                $faktureczka->delete();
                            if ($selsy)
                                foreach ($selsy as $s) {

                                    if ($s->reczna != 1 && $s->zrodlo_transakcji != 5) {
                                        $ebay = ORM::factory('ebay')->where('trans_id', '=', $s->numer_transakcji)->find();
                                        if ($ebay->id != '')
                                            $ebay->delete();
                                    }
                                    $s->delete();
                                }
                        }

                        $faktury = ORM::factory('sell')->where('konto', '=', $cos)->group_by('nr_faktury')->order_by('data', 'ASC')->order_by('id', 'ASC')->find_all();
                        unset($dane);
                        $dane = array();
                        if ($faktury)
                            foreach ($faktury as $fak) {

                                $tem = new takietam();
                                $tem->faktura = $fak;
                                if ($this->session->get('transiki') == 1)
                                    $wiecej = DB::select('id`,`nr_faktury`,`nabywca`, `produkt`, `numer_transakcji`, `zrodlo_transakcji`, `ilosc`, `cena_jednostkowa`, `wartosc`, `waluta`, `data_sprzedazy`, `nrfak`, `wysylka`, GROUP_CONCAT(art_id SEPARATOR \'<br />\') AS `art_id')->from("sells")->where('nr_faktury', '=', $fak->nr_faktury)->and_where('konto', '=', $cos)->group_by('numer_transakcji')->group_by('data_sprzedazy')->group_by('nabywca')->order_by('data', 'ASC')->as_object()->execute();
                                else
                                    $wiecej = DB::select('id`,`nr_faktury`,`nabywca`, `produkt`, `numer_transakcji`, `zrodlo_transakcji`, `ilosc`, `cena_jednostkowa`, `wartosc`, `waluta`, `data_sprzedazy`, `nrfak`, `wysylka`, GROUP_CONCAT(art_id SEPARATOR \'<br />\') AS `art_id')->from("sells")->where('nr_faktury', '=', $fak->nr_faktury)->and_where('numer_transakcji', '!=', '')->and_where('konto', '=', $cos)->group_by('numer_transakcji')->group_by('data_sprzedazy')->group_by('nabywca')->order_by('data', 'ASC')->as_object()->execute();
                                $tem->wiecej = $wiecej;
                                $firma = ORM::factory('firm')->where('id', '=', $cos)->find();
                                $tem->zfirmy = $firma->do_faktury;
                                $fak = ORM::factory('fakture')->where('nr_faktury', '=', $fak->nr_faktury)->and_where('konto', '=', $cos)->find();
                                $tem->vat = $fak->vat;
                                $dane[] = $tem;
                            }

                        $this->template->faktury = $dane;
                    } else {
                        $order = $this->session->get('order');
                        $a = $this->session->get('a');
                        if ($order == $_POST['order'] && $a != 1) {
                            $a = 1;
                            $this->session->set('a', $a);
                        } else if ($order == $_POST['order'] && $a == 1) {
                            $a = 2;
                            $this->session->set('a', $a);
                        }
                        $order = $_POST['order'];
                        $this->session->set('order', $order);
                        if ($a != 1) {
                            if ($order != 'numer') {
                                $faktury = ORM::factory('sell')->where('konto', '=', $cos)->group_by('nr_faktury')->order_by($order, 'ASC')->find_all();
                                unset($dane);
                                $dane = array();
                                if ($faktury)
                                    foreach ($faktury as $fak) {

                                        $tem = new takietam();
                                        $tem->faktura = $fak;
                                        if ($this->session->get('transiki') == 1)
                                            $wiecej = DB::select('id`,`nr_faktury`,`nabywca`, `produkt`, `numer_transakcji`, `zrodlo_transakcji`, `ilosc`, `cena_jednostkowa`, `wartosc`, `waluta`, `data_sprzedazy`, `nrfak`, `wysylka`, GROUP_CONCAT(art_id SEPARATOR \'<br />\') AS `art_id')->from("sells")->where('nr_faktury', '=', $fak->nr_faktury)->and_where('konto', '=', $cos)->group_by('numer_transakcji')->group_by('data_sprzedazy')->group_by('nabywca')->order_by('data', 'ASC')->as_object()->execute();
                                        else
                                            $wiecej = DB::select('id`,`nr_faktury`,`nabywca`, `produkt`, `numer_transakcji`, `zrodlo_transakcji`, `ilosc`, `cena_jednostkowa`, `wartosc`, `waluta`, `data_sprzedazy`, `nrfak`, `wysylka`, GROUP_CONCAT(art_id SEPARATOR \'<br />\') AS `art_id')->from("sells")->where('nr_faktury', '=', $fak->nr_faktury)->and_where('numer_transakcji', '!=', '')->and_where('konto', '=', $cos)->group_by('numer_transakcji')->group_by('data_sprzedazy')->group_by('nabywca')->order_by('data', 'ASC')->as_object()->execute();
                                        $tem->wiecej = $wiecej;
                                        $firma = ORM::factory('firm')->where('id', '=', $cos)->find();
                                        $tem->zfirmy = $firma->do_faktury;
                                        $fak = ORM::factory('fakture')->where('nr_faktury', '=', $fak->nr_faktury)->and_where('konto', '=', $cos)->find();
                                        $tem->vat = $fak->vat;
                                        $dane[] = $tem;
                                    }

                                $this->template->faktury = $dane;
                            } else {

                                $faktury = ORM::factory('sell')->where('konto', '=', $cos)->group_by('nr_faktury')->order_by('data', 'ASC')->find_all();

                                unset($dane);
                                $dane = array();
                                if ($faktury)
                                    foreach ($faktury as $fak) {

                                        $tem = new takietam();
                                        $tem->faktura = $fak;
                                        if ($this->session->get('transiki') == 1)
                                            $wiecej = DB::select('id`,`nr_faktury`,`nabywca`, `produkt`, `numer_transakcji`, `zrodlo_transakcji`, `ilosc`, `cena_jednostkowa`, `wartosc`, `waluta`, `data_sprzedazy`, `nrfak`, `wysylka`, GROUP_CONCAT(art_id SEPARATOR \'<br />\') AS `art_id')->from("sells")->where('nr_faktury', '=', $fak->nr_faktury)->and_where('konto', '=', $cos)->group_by('numer_transakcji')->group_by('data_sprzedazy')->group_by('nabywca')->order_by('data', 'ASC')->as_object()->execute();
                                        else
                                            $wiecej = DB::select('id`,`nr_faktury`,`nabywca`, `produkt`, `numer_transakcji`, `zrodlo_transakcji`, `ilosc`, `cena_jednostkowa`, `wartosc`, `waluta`, `data_sprzedazy`, `nrfak`, `wysylka`, GROUP_CONCAT(art_id SEPARATOR \'<br />\') AS `art_id')->from("sells")->where('nr_faktury', '=', $fak->nr_faktury)->and_where('numer_transakcji', '!=', '')->and_where('konto', '=', $cos)->group_by('numer_transakcji')->group_by('data_sprzedazy')->group_by('nabywca')->order_by('data', 'ASC')->as_object()->execute();
                                        $tem->wiecej = $wiecej;
                                        $firma = ORM::factory('firm')->where('id', '=', $cos)->find();
                                        $tem->zfirmy = $firma->do_faktury;
                                        $fak = ORM::factory('fakture')->where('nr_faktury', '=', $fak->nr_faktury)->and_where('konto', '=', $cos)->find();
                                        $tem->vat = $fak->vat;
                                        $dane[] = $tem;
                                    }

                                $this->template->faktury = $dane;
                            }
                        } else {
                            if ($order != 'numer') {
                                $faktury = ORM::factory('sell')->where('konto', '=', $cos)->group_by('nr_faktury')->order_by($order, 'DESC')->find_all();
                                unset($dane);
                                $dane = array();
                                if ($faktury)
                                    foreach ($faktury as $fak) {

                                        $tem = new takietam();
                                        $tem->faktura = $fak;
                                        if ($this->session->get('transiki') == 1)
                                            $wiecej = DB::select('id`,`nr_faktury`,`nabywca`, `produkt`, `numer_transakcji`, `zrodlo_transakcji`, `ilosc`, `cena_jednostkowa`, `wartosc`, `waluta`, `data_sprzedazy`, `nrfak`, `wysylka`, GROUP_CONCAT(art_id SEPARATOR \'<br />\') AS `art_id')->from("sells")->where('nr_faktury', '=', $fak->nr_faktury)->and_where('konto', '=', $cos)->group_by('numer_transakcji')->group_by('data_sprzedazy')->group_by('nabywca')->order_by('data', 'ASC')->as_object()->execute();
                                        else
                                            $wiecej = DB::select('id`,`nr_faktury`,`nabywca`, `produkt`, `numer_transakcji`, `zrodlo_transakcji`, `ilosc`, `cena_jednostkowa`, `wartosc`, `waluta`, `data_sprzedazy`, `nrfak`, `wysylka`, GROUP_CONCAT(art_id SEPARATOR \'<br />\') AS `art_id')->from("sells")->where('nr_faktury', '=', $fak->nr_faktury)->and_where('numer_transakcji', '!=', '')->and_where('konto', '=', $cos)->group_by('numer_transakcji')->group_by('data_sprzedazy')->group_by('nabywca')->order_by('data', 'ASC')->as_object()->execute();
                                        $tem->wiecej = $wiecej;
                                        $firma = ORM::factory('firm')->where('id', '=', $cos)->find();
                                        $tem->zfirmy = $firma->do_faktury;
                                        $fak = ORM::factory('fakture')->where('nr_faktury', '=', $fak->nr_faktury)->and_where('konto', '=', $cos)->find();
                                        $tem->vat = $fak->vat;
                                        $dane[] = $tem;
                                    }

                                $this->template->faktury = $dane;
                            } else {
                                $faktury = ORM::factory('sell')->where('konto', '=', $cos)->group_by('nr_faktury')->order_by('data', 'DESC')->order_by('nr_faktury', 'DESC')->order_by('id', 'ASC')->find_all();

                                unset($dane);
                                $dane = array();
                                if ($faktury)
                                    foreach ($faktury as $fak) {

                                        $tem = new takietam();
                                        $tem->faktura = $fak;
                                        if ($this->session->get('transiki') == 1)
                                            $wiecej = DB::select('id`,`nr_faktury`,`nabywca`, `produkt`, `numer_transakcji`, `zrodlo_transakcji`, `ilosc`, `cena_jednostkowa`, `wartosc`, `waluta`, `data_sprzedazy`, `nrfak`, `wysylka`, GROUP_CONCAT(art_id SEPARATOR \'<br />\') AS `art_id')->from("sells")->where('nr_faktury', '=', $fak->nr_faktury)->and_where('konto', '=', $cos)->group_by('numer_transakcji')->group_by('data_sprzedazy')->group_by('nabywca')->order_by('data', 'ASC')->as_object()->execute();
                                        else
                                            $wiecej = DB::select('id`,`nr_faktury`,`nabywca`, `produkt`, `numer_transakcji`, `zrodlo_transakcji`, `ilosc`, `cena_jednostkowa`, `wartosc`, `waluta`, `data_sprzedazy`, `nrfak`, `wysylka`, GROUP_CONCAT(art_id SEPARATOR \'<br />\') AS `art_id')->from("sells")->where('nr_faktury', '=', $fak->nr_faktury)->and_where('numer_transakcji', '!=', '')->and_where('konto', '=', $cos)->group_by('numer_transakcji')->group_by('data_sprzedazy')->group_by('nabywca')->order_by('data', 'ASC')->as_object()->execute();
                                        $tem->wiecej = $wiecej;
                                        $firma = ORM::factory('firm')->where('id', '=', $cos)->find();
                                        $tem->zfirmy = $firma->do_faktury;
                                        $fak = ORM::factory('fakture')->where('nr_faktury', '=', $fak->nr_faktury)->and_where('konto', '=', $cos)->find();
                                        $tem->vat = $fak->vat;
                                        $dane[] = $tem;
                                    }

                                $this->template->faktury = $dane;
                            }
                        }
                    }
                }
                if ($_POST['fak']) {
                    require_once(APPPATH . '/faktury/fvatebay.php');
                    require_once(APPPATH . '/faktury/specyfikacja.php');
                    if ($_POST['id']) {


                        $folder = date('Y-m-d');

                        $i = 1;
                        while (file_exists(APPPATH . '/faktury/faktury/' . $folder . $i))
                            $i++;
                        $fol = $folder . $i;

                        mkdir(APPPATH . '/faktury/faktury/' . $fol, 0777, true);
                        chmod(APPPATH . '/faktury/faktury/' . $fol, 0777);

                        function EmptyDir($dirName, $rmDir = false) {
                            if ($dirHandle = opendir($dirName)) {
                                while (false !== ($dirFile = readdir($dirHandle)))
                                    if ($dirFile != "." && $dirFile != "..")
                                        if (!unlink($dirName . "/" . $dirFile))
                                            return false;
                                closedir($dirHandle);
                                if ($rmDir)
                                    if (!rmdir($dirName))
                                        return false;
                                return true;
                            }
                            else
                                return false;
                        }

                        foreach ($_POST['id'] as $id) {




                            $this->SES = Session::instance();
                            $sesjakonto = (int) $this->SES->get('konto');

                            $firma = ORM::factory('firm')->where('id', '=', $sesjakonto)->find();

                            if ($danefak)
                                unset($danefak);
                            $danefakt = array();

                            $ide = ORM::factory('sell')->where('id', '=', $id)->find();

                            $numerfaktury = str_replace('/', '-', $ide->nr_faktury);

                            $selsy = ORM::factory('sell')->where('nr_faktury', '=', $ide->nr_faktury)->and_where('konto', '=', $cos)->find_all();
                            if ($selsy)
                                foreach ($selsy as $e) {
                                    if ($this->session->get('transiki') == 1) {

                                        $dane = new danefaktury();
                                        if ($e->nrfak != null)
                                            $dane->rech = $e->nrfak;
                                        else
                                            $dane->rech = '';
                                        if ($e->art_id != '' && $e->art_id != null)
                                            $dane->sku = $e->art_id;
                                        else
                                            $dane->sku = '';

                                        $dane->tid = $e->numer_transakcji;
                                        $dane->usr = $e->nabywca;
                                        $dane->art_name = $e->produkt;
                                        $dane->ilosc = $e->ilosc;
                                        $dane->price = $e->wartosc;
                                        $dane->wysylka = $e->wysylka;
                                        $dane->datas = $e->data_sprzedazy;
                                        $dane->dataw = $e->data;
                                        $dane->waluta = $e->waluta;
                                        $walet = $dane->waluta;
                                        $dane->nick = $e->nabywca;


                                        $danefakt[] = $dane;
                                    }
                                    else {
                                        if ($e->numer_transakcji != '') {
                                            $dane = new danefaktury();
                                            if ($e->nrfak != null)
                                                $dane->rech = $e->nrfak;
                                            else
                                                $dane->rech = '';
                                            if ($e->art_id != '' && $e->art_id != null)
                                                $dane->sku = $e->art_id;
                                            else
                                                $dane->sku = '';

                                            $dane->tid = $e->numer_transakcji;
                                            $dane->usr = $e->nabywca;
                                            $dane->art_name = $e->produkt;
                                            $dane->ilosc = $e->ilosc;
                                            $dane->price = $e->wartosc;
                                            $dane->wysylka = $e->wysylka;
                                            $dane->datas = $e->data_sprzedazy;
                                            $dane->dataw = $e->data;
                                            $dane->waluta = $e->waluta;
                                            $walet = $dane->waluta;
                                            $dane->nick = $e->nabywca;


                                            $danefakt[] = $dane;
                                        }
                                    }
                                }

                            $datki = explode('-', $ide->data_sprzedazy);

                            $fakturka = ORM::factory('fakture')->where('nr_faktury', '=', $ide->nr_faktury)->and_where('konto', '=', $cos)->find();

                            if (date('w', mktime(0, 0, 0, $datki[1], $datki[2], $datki[0])) == 1) {

                                $datae = date('Y-m-d', mktime(0, 0, 0, $datki[1], $datki[2] - 3, $datki[0]));
                                if ($walet == 'EUR')
                                    $kurs = ORM::factory('kur')->where('data', '=', $datae)->find();
                                else
                                    $kurs = ORM::factory('kure')->where('data', '=', $datae)->find();
                            } else {
                                $datae = date('Y-m-d', mktime(0, 0, 0, $datki[1], $datki[2] - 1, $datki[0]));
                                if ($walet == 'EUR')
                                    $kurs = ORM::factory('kur')->where('data', '=', $datae)->find();
                                else
                                    $kurs = ORM::factory('kure')->where('data', '=', $datae)->find();
                                if ($kurs->id == '') {
                                    $cofanie = 2;
                                    while ($kurs->id == '') {
                                        $datae = date('Y-m-d', mktime(0, 0, 0, $datki[1], $datki[2] - $cofanie, $datki[0]));
                                        if ($walet == 'EUR')
                                            $kurs = ORM::factory('kur')->where('data', '=', $datae)->find();
                                        else
                                            $kurs = ORM::factory('kure')->where('data', '=', $datae)->find();

                                        $cofanie++;
                                    }
                                }
                            }
                            if (!empty($danefakt))
                                specyfikacja($numerfaktury, $fakturka, $danefakt, $firma->do_faktury, $kurs, $_POST['wystawienie'], $_POST['platnosc'], $_POST['rejestracja'], $fol);

                            if ($_POST['jezyk'] == 1) {
                                if (!empty($danefakt))
                                    faktura($numerfaktury, $fakturka, $danefakt, $firma->do_faktury, $kurs, $_POST['wystawienie'], $_POST['platnosc'], $fol);
                            }
                            else if ($_POST['jezyk'] == 2) {
                                if (!empty($danefakt))
                                    if ($walet == 'EUR')
                                        faktura1($numerfaktury, $fakturka, $danefakt, $firma->do_faktury, $kurs, $_POST['wystawienie'], $_POST['platnosc'], $fol);
                                    else
                                        faktura2($numerfaktury, $fakturka, $danefakt, $firma->do_faktury, $kurs, $_POST['wystawienie'], $_POST['platnosc'], $fol);
                            }
                            else {
                                if ($fakturka->jezyk == 1) {
                                    if (!empty($danefakt))
                                        faktura($numerfaktury, $fakturka, $danefakt, $firma->do_faktury, $kurs, $_POST['wystawienie'], $_POST['platnosc'], $fol);
                                }
                                if ($fakturka->jezyk == 2) {
                                    if (!empty($danefakt))
                                        faktura1($numerfaktury, $fakturka, $danefakt, $firma->do_faktury, $kurs, $_POST['wystawienie'], $_POST['platnosc'], $fol);
                                }
                            }
                        }







                        $zip = new ZipArchive();
                        $filename = APPPATH . 'faktury/faktury/' . $fol . '.zip';

                        if (file_exists(APPPATH . 'faktury/faktury/' . $fol)) {

                            if ($zip->open($filename, ZIPARCHIVE::CREATE) !== TRUE) {
                                exit("cannot open <$filename>\n");
                            }

                            foreach (new DirectoryIterator(APPPATH . 'faktury/faktury/' . $fol) as $file)
                                if (!$file->isDot()) {
                                    $zip->addFile(APPPATH . 'faktury/faktury/' . $fol . '/' . $file->getFilename(), $file->getFilename());
                                }
                            $zip->close();

                            $this->response->send_file(APPPATH . '/faktury/faktury/' . $fol . '.zip');
                        }
                    }
                }
            }
        }
    }

    public function action_fakturykorygujacej() {
        $this->template->content = 'lfaktkor'; //załadowanie widoku
        $auth = Auth::instance(); //utworzenie instancji obiektu Auth
        $this->template->jest = 'http://f4s.on-depot.de/' . $this->request->uri();
        if (!$auth->logged_in('login')) { //sprawdzanie czy użytkownik jest zalogowany
            $this->request->redirect('user/login'); //jeśli nie, przekierowujemy do logowania
        } else {

            $dane = array();
            $this->session = Session::instance();
            $cos = $this->session->get('konto');

            $a = 2;

            $faktury = ORM::factory('faktur')->where('konto', '=', $cos)->group_by('nr_faktury')->order_by('data', 'ASC')->find_all();
            if ($faktury)
                foreach ($faktury as $fak) {
                    $tem = new takietam();
                    $tem->faktura = $fak;
                    $wiecej = ORM::factory('faktur')->where('nr_faktury', '=', $fak->nr_faktury)->order_by('data', 'ASC')->find_all();
                    $tem->wiecej = $wiecej;
                    $dane[] = $tem;
                }

            $this->template->faktury = $dane;

            if ($_POST) {
                if (!$_POST['fak']) {
                    if ($_POST['id']) {
                        foreach ($_POST['id'] as $id) {
                            $ide = ORM::factory('faktur')->where('id', '=', $id)->find();

                            $selsy = ORM::factory('faktur')->where('nr_faktury', '=', $ide->nr_faktury)->find_all();

                            if ($selsy)
                                foreach ($selsy as $s) {
                                    $s->delete();
                                }
                        }
                        $faktury = ORM::factory('faktur')->where('konto', '=', $cos)->group_by('nr_faktury')->order_by('data', 'ASC')->find_all();
                        if ($faktury) {
                            unset($dane);
                            $dane = array();
                            foreach ($faktury as $fak) {
                                $tem = new takietam();
                                $tem->faktura = $fak;
                                $wiecej = ORM::factory('faktur')->where('nr_faktury', '=', $fak->nr_faktury)->order_by('data', 'ASC')->find_all();
                                $tem->wiecej = $wiecej;
                                $dane[] = $tem;
                            }

                            $this->template->faktury = $dane;
                        }
                    }
                    if (isset($_POST['order'])) {
                        $order = $this->session->get('order');
                        $a = $this->session->get('a');
                        if ($order == $_POST['order'] && $a != 1) {
                            $a = 1;
                            $this->session->set('a', $a);
                        } else if ($order == $_POST['order'] && $a == 1) {
                            $a = 2;
                            $this->session->set('a', $a);
                        }
                        $order = $_POST['order'];
                        $this->session->set('order', $order);
                        if ($a != 1) {
                            if ($order != 'data') {
                                $faktury = ORM::factory('faktur')->where('konto', '=', $cos)->group_by('nr_faktury')->order_by('data', 'ASC')->order_by($order, 'ASC')->find_all();
                                if ($faktury) {
                                    unset($dane);
                                    $dane = array();
                                    foreach ($faktury as $fak) {
                                        $tem = new takietam();
                                        $tem->faktura = $fak;
                                        $wiecej = ORM::factory('faktur')->where('nr_faktury', '=', $fak->nr_faktury)->order_by('data', 'ASC')->find_all();
                                        $tem->wiecej = $wiecej;
                                        $dane[] = $tem;
                                    }

                                    $this->template->faktury = $dane;
                                }
                            } else {
                                $faktury = ORM::factory('faktur')->where('konto', '=', $cos)->group_by('nr_faktury')->order_by($order, 'ASC')->find_all();
                                if ($faktury) {
                                    unset($dane);
                                    $dane = array();
                                    foreach ($faktury as $fak) {
                                        $tem = new takietam();
                                        $tem->faktura = $fak;
                                        $wiecej = ORM::factory('faktur')->where('nr_faktury', '=', $fak->nr_faktury)->order_by('data', 'ASC')->find_all();
                                        $tem->wiecej = $wiecej;
                                        $dane[] = $tem;
                                    }

                                    $this->template->faktury = $dane;
                                }
                            }
                        } else {
                            if ($order != 'data') {
                                $faktury = ORM::factory('faktur')->where('konto', '=', $cos)->group_by('nr_faktury')->order_by('data', 'DESC')->order_by($order, 'DESC')->find_all();
                                if ($faktury) {
                                    unset($dane);
                                    $dane = array();
                                    foreach ($faktury as $fak) {
                                        $tem = new takietam();
                                        $tem->faktura = $fak;
                                        $wiecej = ORM::factory('faktur')->where('nr_faktury', '=', $fak->nr_faktury)->order_by('data', 'ASC')->find_all();
                                        $tem->wiecej = $wiecej;
                                        $dane[] = $tem;
                                    }

                                    $this->template->faktury = $dane;
                                }
                            } else {
                                $faktury = ORM::factory('faktur')->where('konto', '=', $cos)->group_by('nr_faktury')->order_by($order, 'DESC')->find_all();
                                if ($faktury) {
                                    unset($dane);
                                    $dane = array();
                                    foreach ($faktury as $fak) {
                                        $tem = new takietam();
                                        $tem->faktura = $fak;
                                        $wiecej = ORM::factory('faktur')->where('nr_faktury', '=', $fak->nr_faktury)->order_by('data', 'ASC')->find_all();
                                        $tem->wiecej = $wiecej;
                                        $dane[] = $tem;
                                    }

                                    $this->template->faktury = $dane;
                                }
                            }
                        }
                    }
                }
                if ($_POST['fak']) {



                    require_once(APPPATH . '/faktury/fakturakorekcyjna.php');





                    if ($_POST['id']) {

                        $folder = date('Y-m-d') . '-kor';

                        $i = 1;
                        while (file_exists(APPPATH . '/faktury/faktury/' . $folder . $i))
                            $i++;
                        $fol = $folder . $i;

                        mkdir(APPPATH . '/faktury/faktury/' . $fol, 0777, true);
                        chmod(APPPATH . '/faktury/faktury/' . $fol, 0777);

                        function EmptyDir($dirName, $rmDir = false) {
                            if ($dirHandle = opendir($dirName)) {
                                while (false !== ($dirFile = readdir($dirHandle)))
                                    if ($dirFile != "." && $dirFile != "..")
                                        if (!unlink($dirName . "/" . $dirFile))
                                            return false;
                                closedir($dirHandle);
                                if ($rmDir)
                                    if (!rmdir($dirName))
                                        return false;
                                return true;
                            }
                            else
                                return false;
                        }

                        foreach ($_POST['id'] as $id) {


                            $this->SES = Session::instance();
                            $sesjakonto = (int) $this->SES->get('konto');
                            $firma = ORM::factory('firm')->where('id', '=', $sesjakonto)->find();



                            if ($danefak)
                                unset($danefak);
                            $danefakt = array();

                            $ide = ORM::factory('faktur')->where('id', '=', $id)->find();




                            $numerfaktury = str_replace('/', '-', $ide->nr_faktury);

                            $selsy = ORM::factory('faktur')->where('nr_faktury', '=', $ide->nr_faktury)->find_all();
                            if ($selsy)
                                foreach ($selsy as $e) {

                                    $dane = new danefakturyk();
                                    $dane->art_name = $e->produkt;
                                    $dane->ilosc = $e->ilosc;
                                    $dane->price = $e->netto;
                                    $dane->data = $e->data_korygowanej;
                                    $dane->numer = $e->fak_korygowana;
                                    $dane->art_name1 = $e->produkt1;
                                    $dane->powod = $e->powod;
                                    $dane->ilosc1 = $e->ilosc1;
                                    $dane->price1 = $e->netto1;
                                    $dane->data1 = $e->data;
                                    $dane->waluta = $e->waluta;
                                    $dane->lp = $e->lp;
                                    $dane->sku = $e->sku;
                                    $numerfaktury = $e->nr_faktury;
                                    $jezyk = $e->jezyk;
                                    $danefakt[] = $dane;
                                }
                            $datki = explode('-', $ide->data_korygowanej);
                            if (date('w', mktime(0, 0, 0, $datki[1], $datki[2], $datki[0])) == 1) {

                                $datae = date('Y-m-d', mktime(0, 0, 0, $datki[1], $datki[2] - 3, $datki[0]));
                                $kurs = ORM::factory('kur')->where('data', '=', $datae)->find();
                            } else {
                                $datae = date('Y-m-d', mktime(0, 0, 0, $datki[1], $datki[2] - 1, $datki[0]));
                                $kurs = ORM::factory('kur')->where('data', '=', $datae)->find();
                                if ($kurs->id == '') {
                                    $cofanie = 2;
                                    while ($kurs->id == '') {
                                        $datae = date('Y-m-d', mktime(0, 0, 0, $datki[1], $datki[2] - $cofanie, $datki[0]));
                                        $kurs = ORM::factory('kur')->where('data', '=', $datae)->find();

                                        $cofanie++;
                                    }
                                }
                            }


                            if ($_POST['jezyk'] == 1) {
                                if (!empty($danefakt))
                                    faktura($numerfaktury, $ide, $danefakt, $firma->do_faktury, $kurs, $fol);
                            }
                            else if ($_POST['jezyk'] == 1) {
                                if (!empty($danefakt))
                                    faktura1($numerfaktury, $ide, $danefakt, $firma->do_faktury, $kurs, $fol);
                            }
                            else {
                                if ($jezyk == 1) {
                                    if (!empty($danefakt))
                                        faktura($numerfaktury, $ide, $danefakt, $firma->do_faktury, $kurs, $fol);
                                }
                                if ($jezyk == 2) {
                                    if (!empty($danefakt))
                                        faktura1($numerfaktury, $ide, $danefakt, $firma->do_faktury, $kurs, $fol);
                                }
                            }
                        }




                        $zip = new ZipArchive();
                        $filename = APPPATH . 'faktury/faktury/' . $fol . '.zip';

                        if (file_exists(APPPATH . 'faktury/faktury/' . $fol)) {

                            if ($zip->open($filename, ZIPARCHIVE::CREATE) !== TRUE) {
                                exit("cannot open <$filename>\n");
                            }

                            foreach (new DirectoryIterator(APPPATH . 'faktury/faktury/' . $fol) as $file)
                                if (!$file->isDot()) {
                                    $zip->addFile(APPPATH . 'faktury/faktury/' . $fol . '/' . $file->getFilename(), $file->getFilename());
                                }
                            $zip->close();

                            $this->response->send_file(APPPATH . '/faktury/faktury/' . $fol . '.zip');
                        }
                    }
                }
            }
        }
    }

    public function action_zestawienie() {
        $this->template->content = 'zestawienie'; //załadowanie widoku
        $auth = Auth::instance(); //utworzenie instancji obiektu Auth
        $this->template->jest = 'http://f4s.on-depot.de/' . $this->request->uri();
        if (!$auth->logged_in('login')) { //sprawdzanie czy użytkownik jest zalogowany
            $this->request->redirect('user/login'); //jeśli nie, przekierowujemy do logowania
        } else {

            $this->session = Session::instance();
            $cosik = $this->session->get('konto');

            if ($_GET) {

                $daty = explode(' - ', $_GET['da']);
                $cos = DB::select('id`,`nr_faktury`,`nabywca`, `auction_id`, `nazwisko`, `numer_transakcji`, `zrodlo_transakcji`, `ilosc`, `cena_jednostkowa`, `wartosc`, `waluta`, `data_sprzedazy`, `nrfak`, `wysylka`, GROUP_CONCAT(art_id SEPARATOR \'+\') AS `art_id')->from("sells")->where('data_sprzedazy', 'BETWEEN', array($daty[0], $daty[1]))->group_by('numer_transakcji')->order_by('data_sprzedazy', 'ASC');
                if ($_GET['firmy'] == 0)
                    $cos->and_where('konto', '=', $cosik);
                $this->template->sprzedaz = $cos->as_object()->execute();

                $this->template->konto = $cosik;
            }
        }
    }

}

class takietam {

    public $faktura;
    public $wiecej;
    public $firma;
    public $vat;

}

class danefaktury {

    public $price;
    public $ilosc;
    public $art_name;
    public $datas;
    public $dataw;
    public $waluta;
    public $nick;
    public $numer;
    public $sku;
    public $tid;
    public $usr;
    public $rech;
    public $wysylka;

}

class danefakturykoszty {

    public $price;
    public $ilosc;
    public $art_name;
    public $data;
    public $waluta;
    public $nick;
    public $numer;
    public $sku;
    public $tid;
    public $usr;
    public $rech;
    public $vat;
    public $data_s;

}

class danefakturyk {

    public $price;
    public $price1;
    public $ilosc;
    public $ilosc1;
    public $art_name;
    public $art_name1;
    public $data;
    public $data1;
    public $powod;
    public $waluta;
    public $numer;
    public $lp;
    public $sku;

}
