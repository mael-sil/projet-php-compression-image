<?php


function r($t)
{
    if ($t > 0.04045)
    {
        $t = pow((($t + 0.055) / 1.055),2.4);
    }
    else
    {
        $t = $t / 12.92;
    }
    return $t;
}
function rgb_to_xyz($color)
{
    $var_R = ($color[0] / 255);
    $var_G = ($color[1] / 255);
    $var_B = ($color[2] / 255);

    $var_R = r($var_R);
    $var_G = r($var_G);
    $var_B = r($var_B);
    

    $var_R = $var_R * 100;
    $var_G = $var_G * 100;
    $var_B = $var_B * 100;

    

    $color_xyz[] = $var_R * 0.4124 + $var_G * 0.3576 + $var_B * 0.1805;
    $color_xyz[] = $var_R * 0.2126 + $var_G * 0.7152 + $var_B * 0.0722;
    $color_xyz[] = $var_R * 0.0193 + $var_G * 0.1192 + $var_B * 0.9505;

    return $color_xyz;
}

function f($t)
{
    $gamma = 6/29;
    if($t>pow($gamma,3))
    {
        return pow($t,1/3);
    }else
    {
        return (1/3)*$t*pow($gamma,-2)+4/29;
    }
}

function xyz_to_lab($color)
{
    $reference = array(95.0489,100,108.8840);

    $color_lab [] = 116*f($color[1]/$reference[1])-16;
    $color_lab [] = 500*(f($color[0]/$reference[0])-f($color[1]/$reference[1]));
    $color_lab [] = 200*(f($color[1]/$reference[1])-f($color[2]/$reference[2]));



    return $color_lab;
}

function rgb_to_lab($color)
{
    return xyz_to_lab(rgb_to_xyz($color));
}


function f_inverse($t)
{
    $gamma = 6/29;
    if($t>$gamma)
    {
        return pow($t,3);
    }else
    {
        return 3*pow($gamma,2)*($t-4/29);
    }
}

function lab_to_xyz($color)
{
    $reference = array(95.0489,100,108.8840);

    $color_lab [] = $reference[0]*f_inverse((($color[0]+16)/116) + $color[1]/500);
    $color_lab [] = $reference[1]*f_inverse(($color[0]+16)/116);
    $color_lab [] = $reference[2]*f_inverse((($color[0]+16)/116) - $color[2]/200);



    return $color_lab;
}

function r_inverse($t)
{
    return ($t > 0.0031308) ? 1.055*pow($t,1/2.4)-0.055 : 12.92*$t ;
}

function xyz_to_rgb($color)
{
    $r =  3.2406 * $color[0]  - 1.5372 * $color[1]  - 0.4986 * $color[2] ;
    $g = -0.9689 * $color[0]  + 1.8758 * $color[1]  + 0.0415 * $color[2] ;
    $b =  0.0557 * $color[0]  - 0.2040 * $color[1]  + 1.0570 * $color[2] ;

    $r /= 100;
    $g /= 100;
    $b /= 100;

    $r = max(0, min(1, $r));
    $g = max(0, min(1, $g));
    $b = max(0, min(1, $b));

    $r = r_inverse($r)*255;
    $g = r_inverse($g)*255;
    $b = r_inverse($b)*255;

    return [$r,$g,$b];

}

function lab_to_rgb($color)
{
    return xyz_to_rgb(lab_to_xyz($color));
}
