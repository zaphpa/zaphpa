<?php
require_once 'ZaphpaHtmlView.class.php';

class ZaphpaHtmlTemplates extends Zaphpa_Middleware {

    function prerender(&$buffer) {
      $view = ZaphpaHtmlView::getInstance();
      $contentTpl = $render->getContentTpl();
      $content = $view->render($contentTpl );
      $view->setBlock('content', $content);
        
      $pageTpl = $render->getPageTpl();
      $buffer = $view->render($pageTpl);
    }
    
}