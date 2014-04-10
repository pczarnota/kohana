<?php

defined('SYSPATH') or die('No direct script access.');

class Controller_Edycja extends Controller_Template { //dziedziczenie z Controller_Template

    public $template = 'szablon'; //definiowanie zmiennej do obsługi widoków

    public function action_faktura() {
        $this->template->jest = 'xxxxxxxxxxx' . $this->request->uri();
        $ad = $this->request->param('id');
        $this->template->content = 'efaktury'; //załadowanie widoku
        $auth = Auth::instance(); //utworzenie instancji obiektu Auth
        $this->session = Session::instance();
        $cos = $this->session->get('konto');
        if (!$auth->logged_in('login')) { //sprawdzanie czy użytkownik jest zalogowany
            $this->request->redirect('user/login'); //jeśli nie, przekierowujemy do logowania
        } else {
            $ad = str_replace('-', '/', $ad);
            $this->template->faktury = DB::select('id`,`nr_faktury`,`nabywca`, `produkt`, `numer_transakcji`, `zrodlo_transakcji`, `ilosc`, `cena_jednostkowa`, `wartosc`, `waluta`, `data_sprzedazy`, `nrfak`, `wysylka`, GROUP_CONCAT(art_id SEPARATOR \'<br />\') AS `art_id')->from("sells")->where('nr_faktury', '=', $ad)->and_where('konto', '=', $cos)->group_by('numer_transakcji')->group_by('data_sprzedazy')->group_by('nabywca')->order_by('data', 'ASC')->as_object()->execute();

            $faktury = ORM::factory('sell')->where('nr_faktury', '=', $ad)->and_where('konto', '=', $cos)->find_all();

            $this->template->konta = ORM::factory('firm')->find_all();


            $co = array();
            if ($faktury)
                foreach ($faktury as $faki) {
                    $co[] = $faki->id;
                }

            $data = ORM::factory('sell')->where('nr_faktury', '=', $ad)->and_where('konto', '=', $cos)->find();
            $this->template->data = $data->data;

            $fakturka = ORM::factory('fakture')->where('nr_faktury', '=', $ad)->and_where('konto', '=', $cos)->find();
            $this->template->fakturka = $fakturka;


            if (isset($_POST['id']) && !$_POST['edytuj']) {
                $ide = ORM::factory('sell')->where('id', '=', $_POST['id'])->find();

                $ide->delete();
            }

            if (isset($_POST['edytuj'])) { //sprawdzanie czy dane są przesyłane POSTem
                $walidacja = Validation::factory($_POST); //tworzenie obiektu walidacji
                $walidacja->rule('numer', 'not_empty');
                if ($walidacja->check()) {

                    $sprzedawca = ORM::factory('firm')->where('id', '=', $_POST['sprzedawca'])->find();
                    $kupujacy = ORM::factory('firm')->where('id', '=', $_POST['kupujacy'])->find();

                    $faktury = ORM::factory('sell')->where('nr_faktury', '=', $ad)->and_where('konto', '=', $cos)->find_all();
                    $fakturka = ORM::factory('fakture')->where('nr_faktury', '=', $ad)->and_where('konto', '=', $cos)->find();
                    if (isset($_POST['numer']))
                        $fakturka->nr_faktury = $_POST['numer'];
                    if (isset($_POST['vat']))
                        $fakturka->vat = $_POST['vat'];
                    $fakturka->n_nazwa = $kupujacy->nazwa;
                    $fakturka->n_adres = $kupujacy->adres . '<br />' . $kupujacy->kod_pocztowy . ' ' . $kupujacy->miejscowosc;
                    $fakturka->n_nip = $kupujacy->nip;
                    $fakturka->s_nazwa = $sprzedawca->nazwa;
                    $fakturka->s_kraj = $sprzedawca->kraj;
                    $fakturka->s_adres = $sprzedawca->adres . '<br />' . $sprzedawca->kod_pocztowy . ' ' . $sprzedawca->miejscowosc;
                    $fakturka->s_nip = $sprzedawca->nip;
                    $fakturka->n_kraj = $kupujacy->kraj;
                    if (isset($_POST['jezyk']))
                        $fakturka->jezyk = $_POST['jezyk'];
                    $fakturka->save();


                    foreach ($faktury as $fak) {
                        $fak->nr_faktury = $_POST['numer'];
                        $fak->data = $_POST['data'];
                        $fak->data_sprzedazy = $_POST['data'];

                        $fak->save();
                    }

                    $newad = str_replace('/', '-', $_POST['numer']);
                    $this->request->redirect('/faktura/edytuj/' . $newad);
                } else {
                    $this->template->fail = 'Wpisz nowy numer faktury!';
                }
            }
        }
    }

    public function action_faktkor() {
        $this->template->jest = 'http://f4s.on-depot.de/' . $this->request->uri();
        $ad = $this->request->param('id');
        $this->template->content = 'efaktkor'; //załadowanie widoku
        $auth = Auth::instance(); //utworzenie instancji obiektu Auth

        if (!$auth->logged_in('login')) { //sprawdzanie czy użytkownik jest zalogowany
            $this->request->redirect('user/login'); //jeśli nie, przekierowujemy do logowania
        } else {

            $ad = str_replace('-', '/', $ad);
            $faktura = ORM::factory('faktur')->where('nr_faktury', '=', $ad)->find_all();
            $this->template->konta = ORM::factory('firm')->find_all();
            $this->template->przed = $faktura;

            $numery = ORM::factory('faktur')->where('nr_faktury', '=', $ad)->find();
            $this->template->nr = $numery->nr_faktury;
            $this->template->nr1 = $numery->fak_korygowana;
            $this->template->data = $numery->data;
            $this->template->data1 = $numery->data_korygowanej;
            $this->template->powod = $numery->powod;
            $this->template->jezyk = $numery->jezyk;
            $this->template->vat = $numery->vat;
            $this->template->n_kraj = $numery->n_kraj;
            $this->template->s_nazwa = $numery->s_nazwa;
            $this->template->s_adres = $numery->s_adres;
            $this->template->s_nip = $numery->s_nip;
            $this->template->n_nazwa = $numery->n_nazwa;
            $this->template->n_adres = $numery->n_adres;
            $this->template->n_nip = $numery->n_nip;
            $this->template->n_id = $numery->n_id;
            $this->template->s_id = $numery->s_id;

            if (isset($_POST['id']) && !$_POST['edytuj']) {
                $ide = ORM::factory('faktur')->where('id', '=', $_POST['id'])->find();

                $ide->delete();
            }

            if (isset($_POST['edytuj'])) { //sprawdzanie czy dane są przesyłane POSTem
                $walidacja = Validation::factory($_POST); //tworzenie obiektu walidacji
                $walidacja->rule('nr_faktury1', 'not_empty');
                if ($walidacja->check()) {

                    $sprzedawca = ORM::factory('firm')->where('id', '=', $_POST['sprzedawca'])->find();
                    $kupujacy = ORM::factory('firm')->where('id', '=', $_POST['kupujacy'])->find();

                    $i = 0;
                    $sells = ORM::factory('faktur')->where('nr_faktury', '=', $ad)->find_all();
                    foreach ($sells as $sell) {
                        $sell->data = $_POST['data'];
                        $sell->data_korygowanej = $_POST['data1']; //przypisanie pola z formularza do nazwy kolumny w tabeli
                        $sell->nr_faktury = $_POST['nr_faktury1'];
                        $sell->powod = $_POST['podstawa'];
                        $sell->fak_korygowana = $_POST['nr_faktury'];
                        $sell->produkt = $_POST['dostawca_' . $i];
                        $sell->zrodlo_transakcji = 5;
                        $sell->ilosc = $_POST['ilosc_' . $i];
                        $sell->netto = str_replace(',', '.', $_POST['cena_' . $i]);
                        $sell->waluta = $_POST['waluta'];
                        $sell->jezyk = $_POST['jezyk'];
                        $sell->n_nazwa = $kupujacy->nazwa;
                        $sell->n_adres = $kupujacy->adres . ' ' . $kupujacy->miejscowosc . ' ' . $kupujacy->kod_pocztowy;
                        $sell->n_nip = $kupujacy->nip;
                        $sell->s_nazwa = $sprzedawca->nazwa;
                        $sell->s_adres = $sprzedawca->adres . ' ' . $sprzedawca->miejscowosc . ' ' . $sprzedawca->kod_pocztowy;
                        $sell->s_nip = $sprzedawca->nip;
                        $sell->n_kraj = $kupujacy->kraj;
                        $sell->n_id = $kupujacy->id;
                        $sell->s_id = $sprzedawca->id;
                        $sell->vat = $_POST['vat'];
                        $sell->save();
                        $i++;
                    }

                    $newad = str_replace('/', '-', $_POST['nr_faktury1']);
                    $this->request->redirect('/fakturakor/edytuj/' . $newad);
                } else {
                    $this->template->fail = 'Wpisz nowy numer faktury!';
                }
            }
        }
    }

    public function action_koszty() {
        $this->template->jest = 'http://f4s.on-depot.de/' . $this->request->uri();
        $ad = $this->request->param('id');
        $this->template->content = 'ekoszty'; //załadowanie widoku
        $auth = Auth::instance(); //utworzenie instancji obiektu Auth

        if (!$auth->logged_in('login')) { //sprawdzanie czy użytkownik jest zalogowany
            $this->request->redirect('user/login'); //jeśli nie, przekierowujemy do logowania
        } else {
            $this->template->konta = ORM::factory('firm')->find_all();
            $koszty = ORM::factory('cost')->where('id', '=', $ad)->find();
            $this->template->koszty = $koszty;
            if ($_POST) { //sprawdzanie czy dane są przesyłane POSTem
                $walidacja = Validation::factory($_POST); //tworzenie obiektu walidacji
                $walidacja->rule('dostawca', 'not_empty')
                        ->rule('typ', 'not_empty')
                        ->rule('data', 'not_empty')
                        ->rule('data', 'date')
                        ->rule('cena', 'not_empty')
                        ->rule('ilosc', 'numeric')
                        ->rule('ilosc', 'not_empty');


                if ($walidacja->check()) {

                    $sprzedawca = ORM::factory('firm')->where('id', '=', $_POST['sprzedawca'])->find();
                    $kupujacy = ORM::factory('firm')->where('id', '=', $_POST['kupujacy'])->find();

                    $koszt = ORM::factory('cost')->where('id', '=', $ad)->find(); //tworzenie obiektu ORM z użyciem tabeli users
                    $koszt->sprzedaz = $_POST['dostawca']; //przypisanie pola z formularza do nazwy kolumny w tabeli
                    $koszt->typ = $_POST['typ'];
                    $koszt->cena = str_replace(',', '.', $_POST['cena']);
                    $koszt->ilosc = $_POST['ilosc'];
                    $koszt->data = $_POST['data'];
                    $koszt->data_sprzedazy = $_POST['data'];
                    $koszt->vat = $_POST['vat'];
                    $koszt->n_nazwa = $kupujacy->nazwa;
                    $koszt->n_adres = $kupujacy->adres . '<br />' . $kupujacy->miejscowosc . ' ' . $kupujacy->kod_pocztowy;
                    $koszt->n_nip = $kupujacy->nip;
                    $koszt->s_nazwa = $sprzedawca->nazwa;
                    $koszt->s_adres = $sprzedawca->adres . '<br />' . $sprzedawca->miejscowosc . ' ' . $sprzedawca->kod_pocztowy;
                    $koszt->s_nip = $sprzedawca->nip;
                    $koszt->n_kraj = $kupujacy->kraj;
                    $koszt->s_kraj = $sprzedawca->kraj;
                    $koszt->s_id = $sprzedawca->id;
                    $koszt->n_id = $kupujacy->id;

                    if ($koszt->save()) {
                        $this->template->sukces = 'Edytowano koszty.'; //przekazanie zmiennej $sukces do widoku
                    } else {
                        $this->template->fail = 'Nie udało się edytować kosztów!'; //przekazanie zmiennej $fail do widoku
                    }
                } else {
                    $this->template->fail = 'Uzupełnij poprawnie formularz!';
                }
                $this->template->koszty = $koszt;
            }
        }
    }

    public function action_konta() {
        $this->template->jest = 'http://f4s.on-depot.de/' . $this->request->uri();
        $ad = $this->request->param('id');
        $this->template->content = 'ekonta'; //załadowanie widoku
        $auth = Auth::instance(); //utworzenie instancji obiektu Auth

        if (!$auth->logged_in('login')) { //sprawdzanie czy użytkownik jest zalogowany
            $this->request->redirect('user/login'); //jeśli nie, przekierowujemy do logowania
        } else {

            $koszty = ORM::factory('ebayconfig')->where('id', '=', $ad)->find();
            $this->template->konta = $koszty;
            if ($_POST) { //sprawdzanie czy dane są przesyłane POSTem
                $walidacja = Validation::factory($_POST); //tworzenie obiektu walidacji
                $walidacja->rule('token', 'not_empty')
                        ->rule('devid', 'not_empty')
                        ->rule('appid', 'not_empty')
                        ->rule('certid', 'not_empty')
                        ->rule('name', 'not_empty');


                if ($walidacja->check()) {
                    $koszt = ORM::factory('ebayconfig')->where('id', '=', $ad)->find(); //tworzenie obiektu ORM z użyciem tabeli users
                    $koszt->token = $_POST['token']; //przypisanie pola z formularza do nazwy kolumny w tabeli
                    $koszt->devid = $_POST['devid'];
                    $koszt->appid = $_POST['appid'];
                    $koszt->certid = $_POST['certid'];
                    $koszt->name = $_POST['name'];
                    $koszt->carpar = $_POST['carparst'];

                    if ($koszt->save()) {
                        $this->template->sukces = 'Edytowano konto.'; //przekazanie zmiennej $sukces do widoku
                        $this->request->redirect('konto/lista');
                    } else {
                        $this->template->fail = 'Nie udało się edytować konta!'; //przekazanie zmiennej $fail do widoku
                    }
                } else {
                    $this->template->fail = 'Uzupełnij poprawnie formularz!';
                }
            }
        }
    }

    public function action_sprzedaz() {
        $this->template->jest = 'http://f4s.on-depot.de/' . $this->request->uri();
        $ad = $this->request->param('id');
        $this->template->content = 'esprzedaz'; //załadowanie widoku
        $auth = Auth::instance(); //utworzenie instancji obiektu Auth

        if (!$auth->logged_in('login')) { //sprawdzanie czy użytkownik jest zalogowany
            $this->request->redirect('user/login'); //jeśli nie, przekierowujemy do logowania
        } else {

            $sell = ORM::factory('sell')->where('id', '=', $ad)->find();
            $this->template->sprzedaz = $sell;


            if ($_POST) { //sprawdzanie czy dane są przesyłane POSTem
                $walidacja = Validation::factory($_POST); //tworzenie obiektu walidacji
                $walidacja->rule('nr_faktury', 'not_empty')
                        ->rule('nabywca', 'not_empty')
                        ->rule('produkt', 'not_empty')
                        ->rule('data', 'not_empty')
                        ->rule('data', 'date')
                        ->rule('zrodlo', 'not_empty')
                        ->rule('brutto', 'not_empty')
                        ->rule('wysylka', 'not_empty')
                        ->rule('ilosc', 'numeric')
                        ->rule('waluta', 'not_empty')
                        ->rule('ilosc', 'not_empty');

                if ($walidacja->check()) {
                    $sellik = ORM::factory('sell')->where('id', '=', $ad)->find();
                    $sellsy = ORM::factory('sell')->where('numer_transakcji', '=', $sellik->numer_transakcji)->and_where('nr_faktury', '=', $sellik->nr_faktury)->and_where('nabywca', '=', $sellik->nabywca)->find_all();
                    if ($sellsy)
                        foreach ($sellsy as $sell) {
                            $sell->data = $_POST['data']; //przypisanie pola z formularza do nazwy kolumny w tabeli
                            $sell->nr_faktury = $_POST['nr_faktury'];
                            $sell->nabywca = $_POST['nabywca'];
                            $sell->produkt = $_POST['produkt'];
                            $sell->waluta = $_POST['waluta'];
                            $sell->numer_transakcji = $_POST['nr_transakcji'];
                            if (is_array($_POST['zrodlo'])) {
                                $zrodla = implode(',', $_POST['zrodlo']);
                                $sell->zrodlo_transakcji = $zrodla;
                            }else
                                $sell->zrodlo_transakcji = $_POST['zrodlo'];
                            $sell->ilosc = $_POST['ilosc'];
                            $sell->wysylka = $_POST['wysylka'];
                            $sell->cena_jednostkowa = round(str_replace(',', '.', $_POST['brutto']) / 1.19, 2);
                            $sell->wartosc = str_replace(',', '.', $_POST['brutto']) * $_POST['ilosc'];

                            if ($sell->save()) {
                                $this->template->sukces = 'Edytowano sprzedaż.'; //przekazanie zmiennej $sukces do widoku
                            } else {
                                $this->template->fail = 'Nie udało się edytować sprzedaży!'; //przekazanie zmiennej $fail do widoku
                            }
                        }
                } else {
                    $this->template->fail = 'Uzupełnij poprawnie formularz!';
                }
                $this->template->sprzedaz = $sell;
            }
        }
    }

    public function action_firma() {
        $this->template->jest = 'http://f4s.on-depot.de/' . $this->request->uri();
        $ad = $this->request->param('id');
        $this->template->content = 'efirma'; //załadowanie widoku
        $auth = Auth::instance(); //utworzenie instancji obiektu Auth

        if (!$auth->logged_in('login')) { //sprawdzanie czy użytkownik jest zalogowany
            $this->request->redirect('user/login'); //jeśli nie, przekierowujemy do logowania
        } else {

            $firma = ORM::factory('firm')->where('id', '=', $ad)->find();
            $this->template->firma = $firma;

            if ($_POST) { //sprawdzanie czy dane są przesyłane POSTem
                $walidacja = Validation::factory($_POST); //tworzenie obiektu walidacji
                $walidacja->rule('nazwa', 'not_empty')
                        ->rule('adres', 'not_empty')
                        ->rule('kod_pocztowy', 'not_empty')
                        ->rule('miejscowosc', 'not_empty')
                        ->rule('nip', 'not_empty')
                        ->rule('kraj', 'not_empty');

                if ($walidacja->check()) {
                    $firm = ORM::factory('firm')->where('id', '=', $ad)->find();
                    $firm->nazwa = $_POST['nazwa']; //przypisanie pola z formularza do nazwy kolumny w tabeli
                    $firm->adres = $_POST['adres'];
                    $firm->kod_pocztowy = $_POST['kod_pocztowy'];
                    $firm->miejscowosc = $_POST['miejscowosc'];
                    $firm->nip = $_POST['nip'];
                    $firm->regon = $_POST['regon'];
                    $firm->kraj = $_POST['kraj'];
                    $firm->do_faktury = $_POST['do_faktury'];

                    if ($firm->save()) {
                        $this->template->sukces = 'Edytowanp firmę.'; //przekazanie zmiennej $sukces do widoku
                    } else {
                        $this->template->fail = 'Nie udało się edytować firmy!'; //przekazanie zmiennej $fail do widoku
                    }
                } else {
                    $this->template->fail = 'Uzupełnij poprawnie formularz!';
                }
                $this->template->firma = $firm;
            }
        }
    }

}

class danefaktury {

    public $price;
    public $ilosc;
    public $art_name;
    public $data;
    public $waluta;

}

