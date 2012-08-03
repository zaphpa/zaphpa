<?php
require_once 'ZaphpaHtmlRender.class.php';

class ZaphpaHtmlView extends Zaphpa_Middleware {
    function prerender(&$buffer) {
        $buffer = '';

        $render = ZaphpaHtmlRender::getInstance();
        extract($render->get());

        $content = '';
        ob_start();
        include($render->getContentTpl());
        $content = ob_get_clean();

        $page = '';
        ob_start();
        include($render->getPageTpl());
        $page = ob_get_clean();

        $buffer = $page;
    }
}