<?php
require_once 'ZaphpaHtmlView.class.php';

class ZaphpaHtmlTemplates extends Zaphpa_Middleware {

    function prerender(&$buffer) {
      $view = ZaphpaHtmlView::getInstance();
      $contentTpl = $view->getContentTpl();
      $content = $view->render($contentTpl );
      $view->setBlock('content', $content);

      $pageTpl = $view->getPageTpl();
      $buffer = $view->render($pageTpl);

        print_r($buffer);exit;
    }
    
}