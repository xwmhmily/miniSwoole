<?php
/**
 * File: Security.php
 * Author: 大眼猫
 */

abstract class Security {

    // Anti_SQL Injection, escape quotes
    public static function filterStr($content) {
        if (!get_magic_quotes_gpc()) {
            return addslashes($content);
        } else {
            return $content;
        }
    }

    //对字符串等进行过滤
    public static function filter($arr) {  
        if (!isset($arr)) {
            return null;
        }

        if (is_array($arr)) {
            foreach ($arr as $k => $v) {
                $arr[$k] = trim(self::filterStr(self::stripSQLChars(self::stripHTML($v))));
            }
        } else {
            $arr = trim(self::filterStr(self::stripSQLChars(self::stripHTML($arr))));
        }

        return $arr;
    }

    /**
     *  Strip specail SQL chars
     */
    public static function stripSQLChars($str) {
        $replace = ['DELETE', 'DROP', 'TRUNCATE', 'ALTER'];
        return str_ireplace($replace, '', $str);
    }

    public static function stripHTML($content, $xss = true) {
        $search = array("@<script(.*?)</script>@is",
            "@<iframe(.*?)</iframe>@is",
            "@<style(.*?)</style>@is",
            "@<(.*?)>@is"
        );

        $content = preg_replace($search, '', $content);

        if($xss){
            $ra1 = array('javascript', 'vbscript', 'expression', 'applet', 'meta', 'xml', 'blink', 'link', 
            'style', 'script', 'embed', 'object', 'iframe', 'frame', 'frameset', 'ilayer', 
            'layer', 'bgsound', 'title', 'base');
                                    
            $ra2 = array('onabort', 'onactivate', 'onafterprint', 'onafterupdate', 'onbeforeactivate', 'onbeforecopy',      'onbeforecut', 'onbeforedeactivate', 'onbeforeeditfocus', 'onbeforepaste', 'onbeforeprint', 'onbeforeunload', 'onbeforeupdate', 'onblur', 'onbounce', 'oncellchange', 'onchange', 'onclick', 'oncontextmenu', 'oncontrolselect', 'oncopy', 'oncut', 'ondataavailable', 'ondatasetchanged', 'ondatasetcomplete', 'ondblclick', 'ondeactivate', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onerror', 'onerrorupdate', 'onfilterchange', 'onfinish', 'onfocus', 'onfocusin', 'onfocusout', 'onhelp', 'onkeydown', 'onkeypress', 'onkeyup', 'onlayoutcomplete', 'onload', 'onlosecapture', 'onmousedown', 'onmouseenter', 'onmouseleave', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onmove', 'onmoveend', 'onmovestart', 'onpaste', 'onpropertychange', 'onreadystatechange', 'onreset', 'onresize', 'onresizeend', 'onresizestart', 'onrowenter', 'onrowexit', 'onrowsdelete', 'onrowsinserted', 'onscroll', 'onselect', 'onselectionchange', 'onselectstart', 'onstart', 'onstop', 'onsubmit', 'onunload');
            $ra = array_merge($ra1, $ra2);
            
            $content = str_ireplace($ra, '', $content);
        }

        return strip_tags($content);
    }
}
