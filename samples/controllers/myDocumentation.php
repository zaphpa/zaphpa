<?php

class myDocumentation {
	
  public function getPage($req, $res, $routes) {
    $res->add("<h1>API Documentation:</h1>");
    
    $gets = $routes['get'];
  
    $style = "<style>
                .docs li { width: 90% }
                h2 { margin: 5px 0px 5px 2px; padding:0px; background-color: #EFEFEF;}
                p { margin: 3px 0px; padding: 0px; }
              </style>";
    
    $res->add($style);
    
    $pattern = "<li>
                  <h2>%i</h2>
                  <p>%d</p>
                  <i> <b>File:</b> %f,<b>Class:</b> %c, <b>Method:</b> %m</i>
               </li>";
    
    $return = "<ul class='docs'>\n";
    
    foreach ($gets as $id => $get) {
      $data = array(
        '%i' => $id,
        '%f' => $get['file'],
        '%d' => $get['desc'],
        '%c' => $get['callback'][0],
        '%m' => $get['callback'][1]
      );

      $return .= strtr($pattern, $data);
    }
    
    $return .= "</ul>";
    
    $res->add($return);
    //$res->add(pre($gets));
    $res->send(200);
  }

}
