<?php

require_once 'ZaphpaSingleton.class.php';

class ZaphpaHtmlView extends ZaphpaSingleton {

    private $templateVariables = array();

    private $headerTags = array();
    private $cssTags = array();
    private $javascriptTags = array();
    private $pageTpl = '';
    private $contentTpl = '';
    private $blocks;
    private $jsVars = array();
    private static $RESERVED_VARIABLES = array('headerTags', 'css', 'javascript', 'render', 'content');

    private $tplRoot = '';

    public function __construct() {
        $this->tplRoot = realpath(__DIR__ . '/../../');
    }

    public function get() {
        return $this->templateVariables;
    }

    public function set($varname, $variable) {
        if (in_array($varname, self::$RESERVED_VARIABLES)) {
            throw new Exception("Reserved variable name: $varname");
        }
        if (!empty($varname)) {
            $this->templateVariables[$varname] = $variable;
        }
    }

    public function getBlocks() {
        return $this->blocks;
    }

    public function getBlock($blockName) {
      if (empty($blocks[$blockName])) {
        return '';
      }
      return $this->blocks[$blockName];
    }

    public function setBlock($blockName, $blockRendered) {
        if (!empty($blockName)) {
            $this->blocks[$blockName] = $blockRendered;
        }
    }

    /* == Css / JS == */
    public function setJavascript($javascript) {
        $this->javascriptTags[] = $javascript;
    }

    public function setCss($css) {
        $this->cssTags[] = $css;
    }

    public function getJavascript() {
        $js = '';
        foreach ($this->javascriptTags as $tag) {
            $js .= $tag . "\n";
        }
        return $js . $this->renderJsVars();
    }

    public function getCss() {
        $css = '';
        foreach ($this->cssTags as $tag) {
            $css .= $tag . "\n";
        }
        return $css;
    }

    public function addJs($name, $value) {
        $this->jsVars[$name] = $value;
    }

    /** Outputs Javascript snippet to be inserted in the header of HTML **/
    private function renderJsVars()
    {
        if(empty($this->jsVars))
        {
            return '';
        }

        $embed_prefix = "var zaphpa = {}; zaphpa.vars = {}; zaphpa.vars = ";
        $embed_suffix = ";\n";

        // Encode <, >, ', &, and " using the json_encode() options parameters.
        return ('<script>' . $embed_prefix . json_encode($this->jsVars, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) . $embed_suffix . '</script>');
    }

    /* == Header tags == */

    public function getHeadTags() {
        $head = '';
        foreach ($this->headerTags as $tag) {
            $head .= $tag . "\n";
        };
        return $head;
    }

    public function setHeader($headerName, $arguments) {

        $tag = "<$headerName";

        foreach ($arguments as $key => $value) {
            if (!empty($value)) {
                $tag .= ' ' . $key . '="' . $value . '"';
            }
        }

        $tag .= '>';

        $this->headerTags[] = $tag;
    }

    /* == Template file loading == */

    public function setTplRoot($path) {
        $this->tplRoot = $path;
    }

    public function setContentTpl($contentTplFileLocation) {
        if (file_exists($this->tplRoot . '/' . $contentTplFileLocation . '.tpl.php')) {
            $this->contentTpl = $contentTplFileLocation;
        } else {
            throw Exception('Content TPL path does not exist');
        }
    }

    public function setPageTpl($pageTplFileLocation) {
        if (file_exists($this->tplRoot . '/' . $pageTplFileLocation . '.tpl.php')) {
            $this->pageTpl = $pageTplFileLocation;
        } else {
            throw Exception('Page TPL path does not exist');
        }
    }

    public function getContentTpl() {
        return $this->contentTpl;
    }

    public function getPageTpl() {
        return $this->pageTpl;
    }

    /* == Render Zaphpa Hook == */
    /**
     * @param $tplPath
     *   The name of the tpl file (without the extension) that can also include relative path to the file.
     *
     * @param $blockName
     *   The name of the block as it will be exposed to a wrapper TPL (if any). Defaults
     *   to the filename part of the $tplPath, if none provided.
     **/
    public function render($tplPath, $blockName = null) {
        $blockName = empty($blockName) ? basename($tplPath) : $blockName;
        $render = self::getInstance();
        $headerTags = $render->getHeadTags();
        $css = $render->getCss();
        $javascript = $render->getJavascript();

        ob_start();
        $this->set('blocks', $this->blocks);

        extract($this->get());
        include($this->tplRoot . '/' . $tplPath . '.tpl.php');
        $this->blocks[$blockName] = ob_get_clean();

        return $this->blocks[$tplPath];
    }
}
