<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Service\Invoice;

use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;

class Wkhtml2pdfService {

    private $binFile;
    private $headerLink;
    private $footerLink;
    private $extraArg;
    private $parameters;

    public function __construct(ContainerBagInterface $containerBag) {
        $this->parameters = $containerBag->all();
        $wkhtmltopdf = 'wkhtmltopdf';
        if($containerBag->get('kernel.environment') == 'dev') {
            $wkhtmltopdf .= '.exe';
        }
        $this->binFile = $this->parameters['kernel.project_dir'] . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'invoice' . DIRECTORY_SEPARATOR . $wkhtmltopdf;
        if (file_exists($this->binFile) === false) {
            throw new \Exception('Wkhtmltopdf file not exist!');
        }
    }

    public function getBinFile() {
        return $this->binFile;
    }

    public function getHeaderLink() {
        return $this->headerLink;
    }

    public function setHeaderLink($headerLink) {
        $this->headerLink = $headerLink;
    }

    public function getFooterLink() {
        return $this->footerLink;
    }

    public function setFooterLink($footerLink) {
        $this->footerLink = $footerLink;
    }

    public function setExtraArg($arg) {
        $this->extraArg = $arg;
    }

    public function getExtraArg() {
        return $this->extraArg;
    }

    public function savePdf($html, $output = null) {
        $options = '';
        if ($this->getHeaderLink() !== null) {
            $options .= ' --header-html ' . $this->getHeaderLink();
        }

        if ($this->getFooterLink() !== null) {
            $options .= ' --footer-html ' . $this->getFooterLink();
        }

        $options .= ' ' . $this->getExtraArg();

        $pdf = $this->convertHtml2Pdf($html, $options);
        if ($pdf === false) {
            throw new \Exception('PDF process cancel!');
        }

        if($output !== null) {
            file_put_contents($output, $pdf);
            return true;
        } else {
            return $pdf;
        }
    }

    public function convertHtml2Pdf($HTML, $extraArg = '') {

        // make sure we pass a valid HTML document to the convertor
        //$tidy = new tidy();
        //$HMTL = $tidy->repairString($HMTL);

        $descriptorspec = array(
            0 => array("pipe", "r"), // stdin is a pipe that the child will read from
            1 => array("pipe", "w"), // stdout is a pipe that the child will write to
            2 => array("pipe", "w") // stderr is a file to write to
        );

        $process = proc_open($this->getBinFile() . " {$extraArg} - -", $descriptorspec, $pipes);

        if (!is_resource($process)){
            return false;
        }

        $fp = fwrite($pipes[0], $HTML);
        fclose($pipes[0]);

        $PDF = '';

        $streamPipeStdout = stream_get_contents(($pipes[1]));
        if($streamPipeStdout !== false) {
            $PDF = $streamPipeStdout;
        }
        fclose($pipes[1]);

        $Errors = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        if (mb_strpos($PDF, '%PDF') !== 0) {
            throw new \Exception('Generate PDF file error!' . $Errors);
        }

        proc_close($process);

        return $PDF;
    }

}
