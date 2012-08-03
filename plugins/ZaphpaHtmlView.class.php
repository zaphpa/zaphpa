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
    
    private $tplRoot = '';

    public function __construct() {
        $this->tplRoot = realpath(__DIR__ . '/../../');
    }

    public function get() {
        return $this->templateVariables;
    }

    public function set($varname, $variable) {
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
      ob_start();
      $this->set('blocks', $this->blocks);
      extract($this->get());
      include($this->tplRoot . '/' . $tplPath . '.tpl.php');
      $this->blocks[$tplPath] = ob_get_clean();
      
      return $this->blocks[$tplPath];
    }

    public function getHeadTags() {
        return $this->headerTags;
    }

    public function setHeadMeta($headTagName, $headTagValue) {
        if (!empty($headerName) && !empty($headTagType)) {
            $this->headerTags[] = $this->_formatHeaderTag('head', 'name', $headTagName, 'content', $headTagValue);
        }
    }

    public function setHeadLink($href, $type='', $rel='', $title='') {
        if (!empty($headerName) && !empty($headTagType)) {
            $this->headerTags[] = $this->_formatHeaderTag('head', 'href', $href, 'type', $type,'rel', $rel, 'title', $title);
        }
    }

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

    private function _formatHeaderTag($tagName) {
        $numArgs = func_num_args();
        $arguments = func_get_args();

        if ($numArgs % 2 != 0) {
            $arguments[] = '';
        }

        $tag = "<$tagName";

        for ($i=1; $i < $numArgs; $i+2) {
            $tag .= ' ' . $arguments[$i] . '="' . $arguments[$i+1] . '"';
        }

        $tag = '>';

        return $tag;
    }
}
