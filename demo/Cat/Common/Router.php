<?php

namespace Cat\Common;

class Router extends \Cat\Controller {
//from https://github.com/nikic/FastRoute/blob/master/src/RouteParser/Std.php
/**
* Parses routes of the following form:
*
* "/user/{name}/{id:[0-9]+}"
*/
    const VARIABLE_REGEX = <<<'REGEX'
~\{
\s* ([a-zA-Z][a-zA-Z0-9_]*) \s*
(?:
: \s* ([^{}]*(?:\{(?-1)\}[^{}]*)*)
)?
\}~x
REGEX;
    const DEFAULT_DISPATCH_REGEX = '[^/]+';

    private function parse($route) {
        if (!preg_match_all(
            self::VARIABLE_REGEX, $route, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER
        )) {
            return array($route);
        }

        $offset = 0;
        $routeData = array();
        foreach ($matches as $set) {
            if ($set[0][1] > $offset) {
                $routeData[] = substr($route, $offset, $set[0][1] - $offset);
            }
            $routeData[] = array(
                $set[1][0],
                isset($set[2]) ? trim($set[2][0]) : self::DEFAULT_DISPATCH_REGEX
            );
            $offset = $set[0][1] + strlen($set[0][0]);
        }

        if ($offset != strlen($route)) {
            $routeData[] = substr($route, $offset);
        }

        return $routeData;
    }


    public function index() {
        $route = \Ypf\Lib\Config::get("router");

        $uri = $this->getUri();

        //check static route rule
        if(isset($route['static'][$uri])) {
            $this->request->get['route'] = $route['static'][$uri];
        }else

        foreach ((array)$route['variable'] as $key => $val) {

            $reg_table = $this->buildRegexForRoute($this->parse($key));

            // Does the RegEx match?
            if (preg_match('#^'.$reg_table[0].'$#', $uri, $matches)) {
                $i = 0;
                foreach ($reg_table[1] as $key => $value) {
                    $reg_table[1][$key] = $matches[++$i];

                }
                $this->request->get = array_merge($this->request->get, $reg_table[1]);
                   
                
                $this->request->get['route'] = $val;
                break;
            }
            
        }
        
		if (isset($this->request->get['route'])) {
			return $this->action($this->request->get['route']);
		}

    }

    private function buildRegexForRoute($routeData) {
        $regex = '';
        $variables = array();
        foreach ($routeData as $part) {
            if (is_string($part)) {
                $regex .= preg_quote($part, '~');
                continue;
            }

            list($varName, $regexPart) = $part;

            $variables[$varName] = $varName;
            $regex .= '(' . $regexPart . ')';
        }

        return array($regex, $variables);
    }
    
    private function getUri() {
        $uri = '';
        $_SERVER = array_change_key_case($this->request->server, CASE_UPPER);
        if(!empty($_SERVER['PATH_INFO'])) {
            $uri = $_SERVER['PATH_INFO'];
        }elseif(isset($_SERVER['REQUEST_URI'])) {
            $uri = parse_url(str_replace($_SERVER['SCRIPT_NAME'],'',$_SERVER['REQUEST_URI']), PHP_URL_PATH);
            $uri = rawurldecode($uri);
        }elseif (isset($_SERVER['PHP_SELF'])) {
            $uri = str_replace($_SERVER['SCRIPT_NAME'],'',$_SERVER['PHP_SELF']);
        }
        // Reduce multiple slashes to a single slash and Remove all dot-paths from the URI
        $uri = preg_replace(array('#\.[\s./]*/#','#//+#'), '/', $uri);
        return $uri;
    }
}
?>
