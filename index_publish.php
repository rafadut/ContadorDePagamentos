<?php

require_once($_SERVER['DOCUMENT_ROOT']."/images/stories/crowdfund/PagSeguroLibrary/PagSeguroLibrary.php");
require($_SERVER['DOCUMENT_ROOT']."/images/stories/crowdfund/sendNvpRequest.php");

class SearchTransactionsByDateInterval
{

    public static function main()
    {
        $pageNumber = 1;
        $maxPageResults = 20;

        date_default_timezone_set('America/Sao_Paulo');
        $dataHora = date('Y-m-dh:i', time());

        $dataInicioContagem = date('2017-07-01');
        $dataInicioContagemFormatoPayPal = $dataInicioContagem . 'T00:00:00Z';

        try {

          $credentials = new PagSeguroAccountCredentials("financeiro@vivaedeixeviver.org.br",
              "BE1EE104052DF891DBE34CD57C994F8C");

            $quantidadePagSeguro = 0;
            $ultimaChamada = false;

            while ($dataInicioContagem <= $dataHora) {
                  if ($ultimaChamada != true) {
                      $data = substr($dataHora, 0, 10);
                      $hora = substr($dataHora, 10, 5);
                      $dataInicial = date('Y-m-d',(strtotime ( '-30 day' , strtotime ( $dataHora) ) ));
                      $initialDate = $dataInicial . "T" . $hora;
                      $finalDate = $data . "T" . $hora;

                      $result = PagSeguroTransactionSearchService::searchByDate(
                          $credentials,
                          $pageNumber,
                          $maxPageResults,
                          $initialDate,
                          $finalDate
                      );

                      $transactions = $result->getTransactions();
                      if (is_array($transactions) && count($transactions) > 0) {
                          foreach ($transactions as $key => $transactionSummary) {
                              if ($transactionSummary->getStatus()->getValue() == 4) {
                                $quantidadePagSeguro++;
                              }
                          }
                      }

                      $dataHora = date('Y-m-dh:i',(strtotime ( '-30 day' , strtotime ( $dataHora) ) ));
                      if ($dataInicioContagem > $dataHora) {
                        $dataHora = $dataInicioContagem . $hora;
                        $ultimaChamada = true;
                      }
                  }
                  else {
                    break;
                  }
            }

        } catch (PagSeguroServiceException $e) {
            die($e->getMessage());
        }

        $user = 'financeiro_api1.vivaedeixeviver.org.br';
        $pswd = 'TPB6VN4YPEST8YH3';
        $signature = 'AFcWxV21C7fd0v3bYYYRCpSSRl31AmBitzV.Ixy6frlKpzXTzx67cryV';

        $requestNvp = array(
            'USER' => $user,
            'PWD' => $pswd,
            'SIGNATURE' => $signature,

            'VERSION' => '108.0',
            'METHOD'=> 'TransactionSearch',

            'STARTDATE' => $dataInicioContagemFormatoPayPal,
            'STATUS' => 'Success'
        );

        $responseNvp = sendNvpRequest($requestNvp, false);

        $quantidadePayPal = 0;

        if (isset($responseNvp['ACK']) && $responseNvp['ACK'] == 'Success') {

            for ($i = 0; isset($responseNvp['L_TRANSACTIONID' . $i]); ++$i) {
                $quantidadePayPal++;
            }
        } else {
            print_r("Erro PayPal");
        }

        $quantidadeTotal = $quantidadePagSeguro + $quantidadePayPal;
        $porcentagemTotal = $quantidadeTotal / 1000000;
        if ($porcentagemTotal < 5) {
          $porcentagemTotal = 5;
        }

        $quantidadeStr = str_replace(",", ".", number_format(1000000 - $quantidadeTotal));
        $frase = "Já temos " . $quantidadeTotal ." amigos! Precisamos de mais " . $quantidadeStr . " amigos!";

        $jqueryuicss = "http://www.vivaedeixeviver.org.br/images/stories/crowdfund/jquery-ui.css";
        $jqueryjs = "http://www.vivaedeixeviver.org.br/images/stories/crowdfund/jquery-1.12.4.js";
        $jqueryuijs = "http://www.vivaedeixeviver.org.br/images/stories/crowdfund/jquery-ui.js";

        echo '
          <div id="progressbar"></div>
          <p id="pAmigos">' . $frase . '</p>

          <link rel="stylesheet" href="' . $jqueryuicss . '">

          <style>
          #progressbar, #pAmigos {
            width: 450px;
            font-weight: 600;
            font-family: proxima-nova, sans-serif;
            color: #3f4752;
          }
          .ui-progressbar-value {
            background: #7cc142;
          }
          </style>

          <script src="' . $jqueryjs . '"></script>
          <script src="' . $jqueryuijs . '"></script>
          <script>
          jQuery(document).ready(function($){
            $( "#progressbar" ).progressbar({
              value: ' . $porcentagemTotal . '
            });
          });
          </script>';

    }
}

SearchTransactionsByDateInterval::main();
?>
