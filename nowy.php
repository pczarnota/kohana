<?php

defined('SYSPATH') or die('No direct script access.');

class Controller_Nowy extends Controller_Template { //dziedziczenie z Controller_Template

    public $template = 'szablon'; //definiowanie zmiennej do obsługi widoków

    public function action_koszty() {
        $this->template->content = 'nkoszty'; //załadowanie widoku
        $auth = Auth::instance(); //utworzenie instancji obiektu Auth
        $this->template->jest = 'xxxxxx' . $this->request->uri();
        if (!$auth->logged_in('login')) { //sprawdzanie czy użytkownik jest zalogowany
            $this->request->redirect('user/login'); //jeśli nie, przekierowujemy do logowania
        } else {
            $this->session = Session::instance();
            $cos = $this->session->get('konto');
            $this->template->konta = ORM::factory('firm')->find_all();
            $firma = ORM::factory('firm')->where('id', '=', $cos)->find();
            if ($firma->kraj == 'Polska')
                $vat = 23;
            else
                $vat = 0;

            if ($this->session->get('konto') != '') {
                if ($_POST) { //sprawdzanie czy dane są przesyłane POSTem
                    $walidacja = Validation::factory($_POST); //tworzenie obiektu walidacji
                    $walidacja->rule('sprzedaz', 'not_empty')
                            ->rule('typ', 'not_empty')
                            ->rule('data', 'not_empty')
                            ->rule('data', 'date')
                            ->rule('cena', 'not_empty')
                            ->rule('ilosc', 'numeric')
                            ->rule('ilosc', 'not_empty');


                    if ($walidacja->check()) {

                        $sprzedawca = ORM::factory('firm')->where('id', '=', $_POST['sprzedawca'])->find();
                        $kupujacy = ORM::factory('firm')->where('id', '=', $_POST['kupujacy'])->find();

                        $koszt = ORM::factory('cost'); //tworzenie obiektu ORM z użyciem tabeli users
                        $koszt->sprzedaz = $_POST['sprzedaz']; //przypisanie pola z formularza do nazwy kolumny w tabeli
                        $koszt->typ = $_POST['typ'];
                        $koszt->cena = str_replace(',', '.', $_POST['cena']);
                        $koszt->ilosc = $_POST['ilosc'];
                        $koszt->data = $_POST['data'];
                        $koszt->data_sprzedazy = $_POST['data'];
                        $koszt->waluta = $_POST['waluta'];
                        $koszt->konto = $cos;
                        $koszt->vat = $_POST['vat'];
                        $koszt->n_nazwa = $kupujacy->nazwa;
                        $koszt->n_adres = $kupujacy->adres . '<br />' . $kupujacy->kod_pocztowy . ' ' . $kupujacy->miejscowosc;
                        $koszt->n_nip = $kupujacy->nip;
                        $koszt->s_nazwa = $sprzedawca->nazwa;
                        $koszt->s_adres = $sprzedawca->adres . '<br />' . $sprzedawca->kod_pocztowy . ' ' . $sprzedawca->miejscowosc;
                        $koszt->s_nip = $sprzedawca->nip;
                        $koszt->n_kraj = $kupujacy->kraj;
                        $koszt->n_id = $kupujacy->id;
                        $koszt->s_id = $sprzedawca->id;
                        $koszt->s_kraj = $sprzedawca->kraj;
                        $koszt->jezyk = 1;


                        $rozklad = explode('-', $_POST['data']);

                        $rok = $rozklad[0];
                        $miesiac = $rozklad[1];

                        $faktura = ORM::factory('cost')->where('konto', '=', $cos)->and_where('data', 'LIKE', '%-' . $miesiac . '-%')->order_by('id', 'desc')->find();
                        $numer = explode('/', $faktura->nr);
                        if (isset($numer[1])) {
                            if ($numer[1] == $miesiac) {
                                $nr = (int) $numer[0] + 1;
                                $nrfff = $nr . '/' . $miesiac . '/' . $rok;
                            } else {
                                $nrfff = '1/' . $miesiac . '/' . $rok;
                            }
                        } else {
                            $nrfff = '1/' . $miesiac . '/' . $rok;
                        }
                        $koszt->nr = $nrfff;

                        if ($koszt->save()) {

                            $this->template->sukces = 'Dodano nowe koszty.'; //przekazanie zmiennej $sukces do widoku
                        } else {
                            $this->template->fail = 'Nie udało się dodać kosztów!'; //przekazanie zmiennej $fail do widoku
                        }
                    } else {
                        $this->template->fail = 'Uzupełnij poprawnie formularz!';
                    }
                }
            }
        }
    }

    public function action_sprzedaz() {
        $this->template->content = 'nsprzedaz'; //załadowanie widoku
        $auth = Auth::instance(); //utworzenie instancji obiektu Auth
        $this->template->jest = 'http://f4s.on-depot.de/' . $this->request->uri();
        $this->session = Session::instance();
        $cos = $this->session->get('konto');

        if (!$auth->logged_in('login')) { //sprawdzanie czy użytkownik jest zalogowany
            $this->request->redirect('user/login'); //jeśli nie, przekierowujemy do logowania
        } else {
            if ($this->session->get('konto') != '') {

                $this->template->numery = ORM::factory('sell')->find_all();
                if ($this->request->param('id')) {
                    $ad = $this->request->param('id');
                    $sprzedaz = ORM::factory('sell')->where('id', '=', $ad)->find();
                    $this->template->numer = $sprzedaz->data_sprzedazy;
                }

                if ($_POST) {

                    $sprzedaz = ORM::factory('sell')->where('data_sprzedazy', '=', $_POST['nr_faktury'])->find();
                    $sp = ORM::factory('fakture')->where('nr_faktury', '=', $sprzedaz->nr_faktury)->and_where('konto', '=', $cos)->find();


                    $walidacja = Validation::factory($_POST); //tworzenie obiektu walidacji
                    $walidacja->rule('nr_faktury', 'not_empty')
                            ->rule('nabywca', 'not_empty')
                            ->rule('produkt', 'not_empty')
                            ->rule('cena_jednostkowa', 'not_empty')
                            ->rule('ilosc', 'numeric')
                            ->rule('nr_transakcji', 'numeric')
                            ->rule('nr_transakcji', 'not_empty')
                            ->rule('waluta', 'not_empty')
                            ->rule('ilosc', 'not_empty');

                    if ($walidacja->check()) {
                        $sell = ORM::factory('sell'); //tworzenie obiektu ORM z użyciem tabeli users
                        $sell->data = $sprzedaz->data_sprzedazy;
                        $sell->data_sprzedazy = $sprzedaz->data_sprzedazy; //przypisanie pola z formularza do nazwy kolumny w tabeli
                        $sell->nr_faktury = $sprzedaz->nr_faktury;
                        $sell->nabywca = $_POST['nabywca'];
                        $sell->art_id = $_POST['art_id'];
                        $sell->wysylka = str_replace(',', '.', $_POST['wysylka']);
                        $sell->produkt = $_POST['produkt'];
                        $sell->numer_transakcji = $_POST['nr_transakcji'];
                        $sell->zrodlo_transakcji = 4;
                        $sell->ilosc = $_POST['ilosc'];
                        $sell->cena_jednostkowa = str_replace(',', '.', $_POST['cena_jednostkowa']);
                        $iles = str_replace(',', '.', $_POST['cena_jednostkowa']);
                        $wa = $iles + ($iles * 0.19);
                        $wa1 = $wa * $_POST['ilosc'];
                        $sell->wartosc = str_replace(',', '.', $wa);
                        $sell->waluta = $_POST['waluta'];
                        $sell->konto = $cos;

                        if ($sell->save()) {
                            $this->template->sukces = 'Dodano nową sprzedaż.'; //przekazanie zmiennej $sukces do widoku
                        } else {
                            $this->template->fail = 'Nie udało się dodać sprzedaży!'; //przekazanie zmiennej $fail do widoku
                        }
                    } else {
                        $this->template->fail = 'Uzupełnij poprawnie formularz!';
                    }
                }
            }
        }
    }

    public function action_firma() {
        $this->template->content = 'nfirma'; //załadowanie widoku
        $auth = Auth::instance(); //utworzenie instancji obiektu Auth
        $this->template->jest = 'http://f4s.on-depot.de/' . $this->request->uri();
        $this->session = Session::instance();
        $cos = $this->session->get('konto');

        if (!$auth->logged_in('login')) { //sprawdzanie czy użytkownik jest zalogowany
            $this->request->redirect('user/login'); //jeśli nie, przekierowujemy do logowania
        } else {
            if ($this->session->get('konto') != '') {

                if ($_POST) { //sprawdzanie czy dane są przesyłane POSTem
                    $walidacja = Validation::factory($_POST); //tworzenie obiektu walidacji
                    $walidacja->rule('nazwa', 'not_empty')
                            ->rule('adres', 'not_empty')
                            ->rule('kod_pocztowy', 'not_empty')
                            ->rule('miejscowosc', 'not_empty')
                            ->rule('nip', 'not_empty')
                            ->rule('kraj', 'not_empty');

                    if ($walidacja->check()) {
                        $firm = ORM::factory('firm'); //tworzenie obiektu ORM z użyciem tabeli users
                        $firm->nazwa = $_POST['nazwa']; //przypisanie pola z formularza do nazwy kolumny w tabeli
                        $firm->adres = $_POST['adres'];
                        $firm->kod_pocztowy = $_POST['kod_pocztowy'];
                        $firm->miejscowosc = $_POST['miejscowosc'];
                        $firm->nip = $_POST['nip'];
                        if (isset($_POST['regon']))
                            $firm->regon = $_POST['regon'];
                        $firm->kraj = $_POST['kraj'];
                        if (isset($_POST['do_faktury']))
                            $firm->do_faktury = $_POST['do_faktury'];
                        if (isset($_POST['niewidoczna']))
                            $firm->niewidoczna = 1;

                        if ($firm->save()) {
                            $this->template->sukces = 'Dodano nową firmę.'; //przekazanie zmiennej $sukces do widoku
                            $folder = 'api/' . $firm->id;
                            mkdir($folder);
                            chmod($folder, 0777);
                        } else {
                            $this->template->fail = 'Nie udało się dodać firmy!'; //przekazanie zmiennej $fail do widoku
                        }
                    } else {
                        $this->template->fail = 'Uzupełnij poprawnie formularz!';
                    }
                }
            }
        }
    }

    public function action_faktura() {
        $this->template->content = 'nfaktura'; //załadowanie widoku
        $auth = Auth::instance(); //utworzenie instancji obiektu Auth
        $this->template->jest = 'http://f4s.on-depot.de/' . $this->request->uri();
        $this->session = Session::instance();
        $cos = $this->session->get('konto');

        if (!$auth->logged_in('login')) { //sprawdzanie czy użytkownik jest zalogowany
            $this->request->redirect('user/login'); //jeśli nie, przekierowujemy do logowania
        } else {
            if ($this->session->get('konto') != '') {
                $i = 0;
                $this->template->konta = ORM::factory('firm')->find_all();
                $this->template->ide = $i;
                if (isset($_POST['nowa'])) {

                    $this->template->ide = $_POST['pozycji'];
                    $this->session->set('ile', $_POST['pozycji']);
                }
                if (isset($_POST['submit']) && $this->session->get('ile') > 0) {
                    $ile = $this->session->get('ile');
                    for ($i = 1; $i <= $ile; $i++) {
                        $walidacja = Validation::factory($_POST); //tworzenie obiektu walidacji
                        $walidacja->rule('data', 'date')
                                ->rule('data', 'date')
                                ->rule('nr_faktury', 'not_empty')
                                ->rule('dostawca_' . $i, 'not_empty')
                                ->rule('ilosc_' . $i, 'not_empty')
                                ->rule('ilosc_' . $i, 'numeric')
                                ->rule('cena_' . $i, 'not_empty')
                                ->rule('trans_id_' . $i, 'not_empty')
                                ->rule('waluta', 'not_empty');
                        if ($walidacja->check()) {
                            $sell = ORM::factory('sell'); //tworzenie obiektu ORM z użyciem tabeli users
                            $sell->data = $_POST['data'];
                            $sell->data_sprzedazy = $_POST['data']; //przypisanie pola z formularza do nazwy kolumny w tabeli
                            $sell->nr_faktury = $_POST['nr_faktury'];
                            if ($_POST['nabywca_' . $i] != '')
                                $sell->nabywca = $_POST['nabywca_' . $i];
                            else
                                $sell->nabywca = 0;
                            if ($_POST['art_id_' . $i] != '')
                                $sell->art_id = $_POST['art_id_' . $i];
                            else
                                $sell->art_id = 0;
                            $sell->produkt = $_POST['dostawca_' . $i];
                            if ($_POST['trans_id_' . $i] != '')
                                $sell->numer_transakcji = $_POST['trans_id_' . $i];
                            else
                                $sell->numer_transakcji = 0;
                            $sell->zrodlo_transakcji = 4;
                            $sell->ilosc = $_POST['ilosc_' . $i];
                            $sell->cena_jednostkowa = str_replace(',', '.', $_POST['cena_' . $i]);
                            $wartosc = str_replace(',', '.', $_POST['ilosc_' . $i]) * str_replace(',', '.', $_POST['cena_' . $i] + ($_POST['cena_' . $i] * 0.19));
                            $sell->wartosc = str_replace(',', '.', $wartosc);
                            $sell->waluta = $_POST['waluta'];
                            $sell->reczna = 1;
                            $sell->konto = $cos;
                            $sell->save();

                            $this->template->sukces = 'Dodano nową fakturę.'; //przekazanie zmiennej $sukces do widoku
                        } else {
                            $this->template->fail = 'Uzupełnij poprawnie formularz!';
                        }
                    }
                    if ($walidacja->check()) {
                        $sprzedawca = ORM::factory('firm')->where('id', '=', $_POST['sprzedawca'])->find();
                        $kupujacy = ORM::factory('firm')->where('id', '=', $_POST['kupujacy'])->find();

                        $fakturka = ORM::factory('fakture');
                        $fakturka->nr_faktury = $_POST['nr_faktury'];
                        $fakturka->vat = 0;
                        $fakturka->n_nazwa = $kupujacy->nazwa;
                        $fakturka->n_adres = $kupujacy->adres . '<br />' . $kupujacy->kod_pocztowy . ' ' . $kupujacy->miejscowosc;
                        $fakturka->n_nip = $kupujacy->nip;
                        $fakturka->s_nazwa = $sprzedawca->nazwa;
                        $fakturka->s_adres = $sprzedawca->adres . '<br />' . $sprzedawca->kod_pocztowy . ' ' . $sprzedawca->miejscowosc;
                        $fakturka->s_nip = $sprzedawca->nip;
                        $fakturka->n_kraj = $kupujacy->kraj;
                        $fakturka->s_kraj = $sprzedawca->kraj;
                        $fakturka->jezyk = 1;
                        $fakturka->konto = $cos;
                        $fakturka->n_id = $kupujacy->id;
                        $fakturka->s_id = $sprzedawca->id;
                        $fakturka->save();
                    }
                } else if (isset($_POST['submit']))
                    $this->template->fail = 'Wpisz i kliknij "Wprowadź" aby zatwierdzić ilość pozycji!';
            }
        }
    }

    public function action_fakturakor() {
        $this->template->content = 'nfaktkor'; //załadowanie widoku
        $auth = Auth::instance(); //utworzenie instancji obiektu Auth
        $this->template->jest = 'http://f4s.on-depot.de/' . $this->request->uri();
        $this->session = Session::instance();
        $cos = $this->session->get('konto');

        if (!$auth->logged_in('login')) { //sprawdzanie czy użytkownik jest zalogowany
            $this->request->redirect('user/login'); //jeśli nie, przekierowujemy do logowania
        } else {
            if ($this->session->get('konto') != '') {
                $i = 0;
                $this->template->ide = $i;
                $this->template->konta = ORM::factory('firm')->find_all();

                if (isset($_POST['nowa'])) {

                    $this->template->ide = $_POST['pozycji'];
                    $this->session->set('ile', $_POST['pozycji']);
                }
                if (isset($_POST['submit'])) {
                    $ile = $this->session->get('ile');
                    for ($i = 1; $i <= $ile; $i++) {
                        $firma = ORM::factory('firm')->where('id', '=', $cos)->find();
                        if ($firma->kraj != "Polska")
                            $vat = 0;
                        else
                            $vat = 23;
                        $walidacja = Validation::factory($_POST); //tworzenie obiektu walidacji
                        $walidacja->rule('data', 'date')
                                ->rule('data1', 'date')
                                ->rule('nr_faktury', 'not_empty')
                                ->rule('nr_faktury1', 'not_empty')
                                ->rule('podstawa', 'not_empty')
                                ->rule('dostawca_' . $i, 'not_empty')
                                ->rule('ilosc_' . $i, 'not_empty')
                                ->rule('ilosc_' . $i, 'numeric')
                                ->rule('waluta', 'not_empty');
                        if ($walidacja->check()) {

                            $sprzedawca = ORM::factory('firm')->where('id', '=', $_POST['sprzedawca'])->find();
                            $kupujacy = ORM::factory('firm')->where('id', '=', $_POST['kupujacy'])->find();


                            $sell = ORM::factory('faktur'); //tworzenie obiektu ORM z użyciem tabeli users
                            $sell->data = $_POST['data1'];
                            $sell->data_korygowanej = $_POST['data']; //przypisanie pola z formularza do nazwy kolumny w tabeli
                            $sell->nr_faktury = $_POST['nr_faktury1'];
                            $sell->powod = $_POST['podstawa'];
                            $sell->fak_korygowana = $_POST['nr_faktury'];
                            $sell->produkt1 = $_POST['dostawca_' . $i];
                            $sell->ilosc1 = 0;
                            $sell->netto1 = 0;
                            $sell->produkt = $_POST['dostawca_' . $i];
                            $sell->lp = $_POST['lp_' . $i];
                            $sell->sku = $_POST['sku_' . $i];
                            $sell->zrodlo_transakcji = 5;
                            $sell->ilosc = $_POST['ilosc_' . $i];
                            $sell->netto = str_replace(',', '.', $_POST['cena_' . $i]);
                            $sell->waluta = $_POST['waluta'];
                            $sell->konto = $cos;
                            $sell->vat = $vat;
                            $sell->s_nazwa = $sprzedawca->nazwa;
                            $sell->s_adres = $sprzedawca->adres . '<br />' . $sprzedawca->kod_pocztowy . ' ' . $sprzedawca->miejscowosc;
                            $sell->s_nip = $sprzedawca->nip;
                            $sell->n_nazwa = $kupujacy->nazwa;
                            $sell->n_adres = $kupujacy->adres . '<br />' . $kupujacy->kod_pocztowy . ' ' . $kupujacy->miejscowosc;
                            $sell->n_nip = $kupujacy->nip;
                            $sell->n_kraj = $kupujacy->kraj;
                            $sell->s_id = $sprzedawca->id;
                            $sell->s_kraj = $sprzedawca->kraj;
                            $sell->n_id = $kupujacy->id;
                            $sell->save();

                            $this->template->sukces = 'Dodano nową fakturę korygującą.'; //przekazanie zmiennej $sukces do widoku	
                        } else {
                            $this->template->fail = 'Uzupełnij poprawnie formularz!';
                        }
                    }
                }
            }
        }
    }

    public function action_import() {
        $this->template->content = 'import'; //załadowanie widoku
        $auth = Auth::instance(); //utworzenie instancji obiektu Auth
        $this->template->jest = 'http://f4s.on-depot.de/' . $this->request->uri();
        $this->session = Session::instance();
        $cos = $this->session->get('konto');

        if (!$auth->logged_in('login')) { //sprawdzanie czy użytkownik jest zalogowany
            $this->request->redirect('user/login'); //jeśli nie, przekierowujemy do logowania
        } else {
            $this->template->konta = ORM::factory('firm')->find_all();
            if ($_POST) {

                $sprzedawca = ORM::factory('firm')->where('id', '=', $_POST['sprzedawca'])->find();
                $kupujacy = ORM::factory('firm')->where('id', '=', $_POST['kupujacy'])->find();

                require_once(APPPATH . '/xlsx/PHPExcel.php');
                $fileName = $_FILES["plik"]["tmp_name"];
                $excel = PHPExcel_IOFactory::load($fileName);
                if (is_object($excel->getSheetByName($_POST['nazwa']))) {
                    $data = $excel->getSheetByName($_POST['nazwa'])->toArray();
                    $ilosc = 0;
                    foreach ($data as $dat) {
                        if ($dat[0] != '')
                            $ilosc++;
                    }


                    $ile = 0;
                    for ($i = 1; $i <= $ilosc - 1; $i++) {
                        $nr = $data[$i][2];

                        preg_match('/(\d{2})-(\d{2})-(\d{2})/', $data[$i][10], $matches);
                        $datyczka = '20' . $matches[3] . '-' . $matches[1] . '-' . $matches[2];

                        $czek = ORM::factory('kur')->where('data', '=', $datyczka)->find();
                        if ($czek->id == '') {
                            $wstaw = ORM::factory('kur');


                            $wstaw->data = $datyczka;
                            $wstaw->kurs = str_replace(',', '.', $data[$i][9]);
                            $wstaw->save();
                        }


                        $a = 1;
                        $num = $data[$i][4];

                        preg_match('/(\d{2})-(\d{2})-(\d{2})/', $data[$i][5], $matches1);
                        $datyczka2 = '20' . $matches1[3] . '-' . $matches1[1] . '-' . $matches1[2];

                        preg_match('/(\d{2})-(\d{2})-(\d{2})/', $data[$i][3], $matches2);
                        $datyczka3 = '20' . $matches2[3] . '-' . $matches2[1] . '-' . $matches2[2];



                        $spr = ORM::factory('faktur')->where('data', '=', $datyczka2)->and_where('fak_korygowana', '=', $nr)->find();
                        if ($spr->id != '') {
                            $numeros = $spr->nr_faktury;
                        } else {
                            $spr2 = DB::query(Database::SELECT, 'SELECT * FROM `fakturs` WHERE `nr_faktury` LIKE \'%' . substr($data[$i][1], 1) . '%\' AND `konto` = ' . $cos . ' ORDER BY substring_index(`nr_faktury`, \'/\', 1) * 1 DESC LIMIT 1')->as_object()->execute('default');

                            //$spr2 = ORM::factory('faktur')->where('nr_faktury', 'LIKE', '%'.substr($data[$i][1], 1).'%')->order_by('nr_faktury', 'DESC')->find();
                            if ($spr2[0]->id != '') {
                                $num = explode('/', $spr2[0]->nr_faktury);
                                $mu = $num[0] + 1;
                                $numeros = $mu . '/' . $data[$i][1];
                            }
                            else
                                $numeros = '1/' . $data[$i][1];
                        }

                        $sell = ORM::factory('faktur'); //tworzenie obiektu ORM z użyciem tabeli users
                        $sell->data = $datyczka2;
                        $sell->data_korygowanej = $datyczka3; //przypisanie pola z formularza do nazwy kolumny w tabeli
                        $sell->nr_faktury = $numeros;
                        $sell->powod = 'Zwrot';
                        $sell->fak_korygowana = $nr;
                        $sell->produkt1 = $data[$i][7];
                        $sell->ilosc1 = 0;
                        $sell->netto1 = 0;
                        $sell->produkt = $data[$i][7];
                        $sell->lp = $data[$i][4];
                        $sell->sku = $data[$i][6];
                        $sell->zrodlo_transakcji = 5;
                        $sell->ilosc = 1;
                        $sell->netto = str_replace(',', '.', $data[$i][8]);
                        $sell->waluta = 'EUR';
                        $sell->konto = $cos;
                        $sell->vat = 0;
                        $sell->s_nazwa = $sprzedawca->nazwa;
                        $sell->s_kraj = $sprzedawca->kraj;
                        $sell->s_adres = $sprzedawca->adres . '<br />' . $sprzedawca->kod_pocztowy . ' ' . $sprzedawca->miejscowosc . ' ' . $sprzedawca->kod_pocztowy;
                        $sell->s_nip = $sprzedawca->nip;
                        $sell->n_nazwa = $kupujacy->nazwa;
                        $sell->n_adres = $kupujacy->adres . '<br />' . $kupujacy->kod_pocztowy . ' ' . $kupujacy->miejscowosc;
                        $sell->n_nip = $kupujacy->nip;
                        $sell->n_kraj = $kupujacy->kraj;
                        $sell->n_id = $kupujacy->id;
                        $sell->s_id = $sprzedawca->id;
                        if ($sell->save())
                            $ile++;
                    }
                }
                else {

                    $this->template->fail = 'Błędny plik lub nazwa arkusza';
                }
                if ($ile > 0)
                    $this->template->sukces = 'Wczytano faktury';
                else
                    $this->template->fail = 'Nie wczytano danych. Sprawdź poprawność arkusza.';
            }
        }
    }

    public function action_import2() {
        $this->template->content = 'import2'; //załadowanie widoku
        $auth = Auth::instance(); //utworzenie instancji obiektu Auth
        $this->template->jest = 'http://f4s.on-depot.de/' . $this->request->uri();
        $this->session = Session::instance();
        $cos1 = $this->session->get('konto');

        if (!$auth->logged_in('login')) { //sprawdzanie czy użytkownik jest zalogowany
            $this->request->redirect('user/login'); //jeśli nie, przekierowujemy do logowania
        } else {
            $this->template->konta = ORM::factory('firm')->find_all();
            if ($_POST) {

                $sprzedawca = ORM::factory('firm')->where('id', '=', $_POST['sprzedawca'])->find();
                $kupujacy = ORM::factory('firm')->where('id', '=', $_POST['kupujacy'])->find();

                require_once(APPPATH . '/xlsx/PHPExcel.php');
                if (strlen($_FILES["plik"]["tmp_name"])) {
                    $fileName = $_FILES["plik"]["tmp_name"];
                    $excel = PHPExcel_IOFactory::load($fileName);
                }


                $rozklad = explode('-', $_POST['data']);

                $rok = $rozklad[0];
                $miesiac = $rozklad[1];
                $faktura = ORM::factory('sell')->where('konto', '=', $cos1)->and_where('data', 'LIKE', '%-' . $miesiac . '-%')->and_where('nr_faktury', 'LIKE', '%/' . $miesiac . '/%')->order_by('id', 'desc')->find();
                $numer = explode('/', $faktura->nr_faktury);
                if (isset($numer[1])) {
                    if ($numer[1] == $miesiac) {
                        $nr = (int) $numer[0];
                        //do{
                        $nr++;
                        $numerfaktury = $nr . '-' . $miesiac . '-' . $rok;
                        $nrfff = $nr . '/' . $miesiac . '/' . $rok;
                        //}while($cos->id);
                    } else {
                        $numerfaktury = '1-' . $miesiac . '-' . $rok;
                        $nrfff = '1/' . $miesiac . '/' . $rok;
                    }
                } else {
                    $numerfaktury = '1-' . $miesiac . '-' . $rok;
                    $nrfff = '1/' . $miesiac . '/' . $rok;
                }

                if (is_object($excel->getSheetByName($_POST['nazwa']))) {
                    $data = $excel->getSheetByName($_POST['nazwa'])->toArray();
                    $ilosc = 0;
                    foreach ($data as $dat) {
                        if ($dat[0] != '')
                            $ilosc++;
                    }
                    $ile = 0;
                    for ($i = 1; $i <= $ilosc - 1; $i++) {


                        $fak = ORM::factory('sell');
                        $fak->data = $_POST['data'];
                        $fak->nr_faktury = $nrfff;
                        $fak->nabywca = $data[$i][7] . ' ' . $data[$i][8];
                        $fak->art_id = $data[$i][2];
                        $fak->data_sprzedazy = $_POST['data'];
                        $fak->produkt = $data[$i][5];
                        $fak->waluta = 'EUR';
                        $tran = explode('.', $data[$i][0]);
                        $fak->numer_transakcji = $tran[0];
                        $fak->zrodlo_transakcji = 4;
                        $fak->nazwisko = $data[$i][7] . ' ' . $data[$i][8];
                        $fak->wysylka = 0;
                        $fak->ilosc = $data[$i][4];
                        $cena = round(str_replace(',', '.', $data[$i][13]) / str_replace(',', '.', $data[$i][4]), 2);
                        $fak->cena_jednostkowa = $cena;
                        $fak->wartosc = str_replace(',', '.', $data[$i][12]);
                        $fak->konto = $cos1;
                        $fak->reczna = 1;
                        $fak->save();
                        if ($fak->save())
                            $ile++;
                        if ($i == 1) {

                            $fak1 = ORM::factory('fakture');
                            $fak1->nr_faktury = $nrfff;
                            $fak1->vat = 0;
                            $fak1->s_nazwa = $sprzedawca->nazwa;
                            $fak1->s_adres = $sprzedawca->adres . '<br />' . $sprzedawca->kod_pocztowy . ' ' . $sprzedawca->miejscowosc;
                            $fak1->s_nip = $sprzedawca->nip;
                            $fak1->n_nazwa = $kupujacy->nazwa;
                            $fak1->n_adres = $kupujacy->adres . '<br />' . $kupujacy->kod_pocztowy . ' ' . $kupujacy->miejscowosc;
                            $fak1->n_nip = $kupujacy->nip;
                            $fak1->jezyk = 1;
                            $fak1->n_kraj = $kupujacy->kraj;
                            $fak1->s_kraj = $sprzedawca->kraj;
                            $fak1->konto = $cos1;
                            $fak1->n_id = $kupujacy->id;
                            $fak1->s_id = $sprzedawca->id;
                            $fak1->save();
                        }
                    }
                } else {

                    $this->template->fail = 'Błędny plik lub nazwa arkusza';
                }
                if ($ile > 0)
                    $this->template->sukces = 'Wczytano faktury';
                else
                    $this->template->fail = 'Nie wczytano danych. Sprawdź poprawność arkusza.';
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
    public $korekcja;

}