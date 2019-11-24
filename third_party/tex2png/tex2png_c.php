<?php

namespace Gregwar\Tex2png;

use Gregwar\Cache\Cache;

/**
 * Helper to generate PNG from LaTeX formula
 *
 * @author Grégoire Passault <g.passault@gmail.com>
 */
class Tex2png
{
    const LATEX = "/usr/bin/latex";
    const DVIPNG = "/usr/bin/dvipng";

    /**
     * LaTeX packges
     */
    public $packages = array('amssymb,amsmath', 'color', 'amsfonts', 'amssymb');

    public $tmpDir = '/tmp/tex2png';
    public $file = null;

    /**
     * Target actual file
     */
    public $actualFile = null;
    public $hash;
    public $formula;
    public $density;
    public $error = null;

    public function __construct($formula, $density = 155)
    {
        is_dir($this->tmpDir) or mkdir($this->tmpDir);
        $this->formula = $formula;
        $this->density = $density;
        $this->hash = sha1($formula.$density);
    }

    /**
     * Sets the target directory
     */
    public function saveTo($file)
    {
        $this->actualFile = $this->file = $file;
        return $this;
    }

    /**
     * Generates the image
     */
    public function generate()
    {
        $tex2png = $this;
        $generate = function($target) use ($tex2png) {
            $tex2png->actualFile = $target;
            try {
                $tex2png->createFile();
                $tex2png->latexFile();
                $tex2png->dvi2png();
            } catch (\Exception $e) {
                $tex2png->error = $e;
            }
            $tex2png->clean();
        };

        if ($this->actualFile === null) {
            $target = $this->hash.'.png';
        } else {
            $generate($this->actualFile);
        }

        return $this;
    }

    /**
     * Create the LaTeX file
     */
    public function createFile()
    {
        $tmpfile = $this->tmpDir.'/'.$this->hash.'.tex';
        $tex = 
            '\documentclass[12pt]{article}'."\n".
            '\usepackage[utf8]{inputenc}'."\n";

        foreach ($this->packages as $package) {
            $tex .= '\usepackage{' . $package . "}\n";
        }
        
        $tex .= 
            "\\begin{document}\n".
            "\\pagestyle{empty}\n".
            "\\begin{displaymath}\n".
            $this->formula."\n".
            "\\end{displaymath}\n".
            "\\end{document}\n";

        if (file_put_contents($tmpfile, $tex) === false) {
            throw new \Exception('Failed to open target file');
        }
    }

    /**
     * Compiles the LaTeX to DVI
     */
    public function latexFile()
    {
        $command = 'cd ' . $this->tmpDir . '; ' . static::LATEX . ' ' . $this->hash . '.tex < /dev/null |grep ^!|grep -v Emergency > ' . $this->tmpDir . '/' .$this->hash . '.err 2> /dev/null 2>&1';
        shell_exec($command);
        if (!file_exists($this->tmpDir . '/' . $this->hash . '.dvi')) {
            throw new \Exception('Unable to compile LaTeX formula (is latex installed? check syntax)');
        }
    }

    /**
     * Converts the DVI file to PNG
     */
    public function dvi2png()
    {
        // XXX background: -bg 'rgb 0.5 0.5 0.5'
        $command = static::DVIPNG.' -q -T tight -D ' . $this->density . ' -o ' . $this->actualFile . ' ' . $this->tmpDir . '/' . $this->hash . '.dvi 2>&1';
        if (shell_exec($command) === null) {
            throw new \Exception('Unable to convert the DVI file to PNG (is dvipng installed?)');
        }
    }

    /**
     * Cleaning
     */
    public function clean()
    {
        @shell_exec('rm -f '.$this->tmpDir.'/'.$this->hash.'.* 2>&1');
    }
}
