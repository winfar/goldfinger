<?php 
/**
* 验证手机号是否正确
* @author liuwei
* @param INT $mobile
*/
function isMobile($mobile) {
    if (!is_numeric($mobile)) {
        return 101;
    }
    return preg_match('#^13[\d]{9}$|^14[5,7]{1}\d{8}$|^15[^4]{1}\d{8}$|^17[0,6,7,8]{1}\d{8}$|^18[\d]{9}$#', $mobile) ? 200 : 101;
}
function isEmail($url)   
{   
	return filter_var($url, FILTER_VALIDATE_EMAIL)!==false ? 200 :100 ;exit;
}
?>