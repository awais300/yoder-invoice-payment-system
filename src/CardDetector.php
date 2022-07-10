<?php

namespace Yoder\YIPS;

defined('ABSPATH') || exit;


/**
 * Script taken from https://stackoverflow.com/a/21617574/1509720
 * Made slight modifications
 * 
 **/


/**
 * Class CardDetector
 * @package Yoder\YIPS
 */
class CardDetector extends Singleton
{
    /**
     * Obtain a brand constant from a PAN.
     *
     * @param string $pan Credit card number
     * @param bool   $include_sub_types Include detection of sub visa brands
     * @return string
     */
    public function detect($pan, $include_sub_types = false)
    {
        //maximum length is not fixed now, there are growing number of CCs has more numbers in length, limiting can give false negatives atm

        //these regexps accept not whole cc numbers too
        
        //visa
        $visa_regex = "/^4[0-9]{0,}$/";
        $vpreca_regex = "/^428485[0-9]{0,}$/";
        $postepay_regex = "/^(402360|402361|403035|417631|529948){0,}$/";
        $cartasi_regex = "/^(432917|432930|453998)[0-9]{0,}$/";
        $entropay_regex = "/^(406742|410162|431380|459061|533844|522093)[0-9]{0,}$/";
        $o2money_regex = "/^(422793|475743)[0-9]{0,}$/";

        // MasterCard
        $mastercard_regex = "/^(5[1-5]|222[1-9]|22[3-9]|2[3-6]|27[01]|2720)[0-9]{0,}$/";
        $maestro_regex = "/^(5[06789]|6)[0-9]{0,}$/";
        $kukuruza_regex = "/^525477[0-9]{0,}$/";
        $yunacard_regex = "/^541275[0-9]{0,}$/";

        // American Express
        $amex_regex = "/^3[47][0-9]{0,}$/";

        // Diners Club
        $diners_regex = "/^3(?:0[0-59]{1}|[689])[0-9]{0,}$/";

        //Discover
        $discover_regex = "/^(6011|65|64[4-9]|62212[6-9]|6221[3-9]|622[2-8]|6229[01]|62292[0-5])[0-9]{0,}$/";

        //JCB
        $jcb_regex = "/^(?:2131|1800|35)[0-9]{0,}$/";

        //ordering matter in detection, otherwise can give false results in rare cases
        if (preg_match($jcb_regex, $pan)) {
            return "JCB";
        }

        if (preg_match($amex_regex, $pan)) {
            return "American Express";
        }

        if (preg_match($diners_regex, $pan)) {
            return "Diner's Club";
        }

        //sub visa/mastercard cards
        if ($include_sub_types) {
            if (preg_match($vpreca_regex, $pan)) {
                return "v-preca";
            }
            if (preg_match($postepay_regex, $pan)) {
                return "postepay";
            }
            if (preg_match($cartasi_regex, $pan)) {
                return "cartasi";
            }
            if (preg_match($entropay_regex, $pan)) {
                return "entropay";
            }
            if (preg_match($o2money_regex, $pan)) {
                return "o2money";
            }
            if (preg_match($kukuruza_regex, $pan)) {
                return "kukuruza";
            }
            if (preg_match($yunacard_regex, $pan)) {
                return "yunacard";
            }
        }

        if (preg_match($visa_regex, $pan)) {
            return "Visa";
        }

        if (preg_match($mastercard_regex, $pan)) {
            return "MasterCard";
        }

        if (preg_match($discover_regex, $pan)) {
            return "Discover";
        }

        if (preg_match($maestro_regex, $pan)) {
            if ($pan[0] == '5') { //started 5 must be mastercard
                return "MasterCard";
            }
            return "Maestro"; //maestro is all 60-69 which is not something else, thats why this condition in the end

        }

        return "Unknown"; //unknown for this system
    }
}
