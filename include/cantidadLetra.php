<?php
// CodePesos ========================================================
/*******************************************************
* WRITTEN AMOUNT, spanish languaje conversion script   *
*                                                      * 
* This can be used as pseudocode for porting to other  *
* languajes. The script will convert an amount between *
* 0 and 999,999,999 to a spanish wirtten amount which  *
* is sintactically correct and handles special cases   *
* in spanish                                           *
*                                                      *
* Written by: Hector Lecuanda                          *
*             hector.lecuanda@driessenusa.com          *
* Language: php 3                                      *
* Version 1.0                                          *
* Date: September 1st 1999                             *
*                                                      *
*******************************************************/

// function mod (modulus) is basically self explanatory. It returns the reminder
// of a division.
function mod($a , $b) {
  $c = $a / $b ; 
  $whole_part = floor($c); // this line gets you the integer part of the quotient
  $fraction_part = $c - $whole_part ; // this line gets you the fractional part of the quotient
  $modulus = round($fraction_part * $b); // this line gives you the modulus (fraction times divisor)
  return $modulus;

} // end of function integer_part() ============================================


// function whole_part returns the whole part of a quotient
function whole_part($a , $b) {
  $c = $a / $b ; 
  $w_part = floor($c);
  return $w_part;

} //end of function mod() ======================================================

// function fraction part, returns the fractional part of a quotient
function fraction_part($a , $b) {
  $c = $a / $b ; 
  $f_part = floor($c);
  return $f_part;

} //end of function mod() ======================================================


// function string_literal conversion is the core of this program
// converts numbers to spanish strings, handling the general special
// cases in spanish language. 
function string_literal_conversion($number) {

// first, divide your number in hundreds, tens and units, cascadig
// trough subsequent divisions, using the modulus of each division
// for the next. 

$hundreds  = whole_part($number, 100);
$number = mod($number, 100);

$tens      = whole_part($number, 10);
$number = mod($number, 10);

$units     = whole_part($number, 1);
$number = mod($number, 1); 

/* uncomment for debugging 

print('hundreds : '.$hundreds. '<br>');
print('tens     : '.$tens.     '<br>');
print('units    : '.$units.    '<br><hr>');

*/

// cascade trough hundreds. This will convert the hundreds part to 
// their corresponding string in spanish. 
switch ($hundreds) {
    case 1: $string_hundreds = "ciento ";        break; // Special case
    case 2: $string_hundreds = "doscientos ";    break;
    case 3: $string_hundreds = "trescientos ";   break;
    case 4: $string_hundreds = "cuatrocientos "; break;
    case 5: $string_hundreds = "quinientos ";    break; // Special case
    case 6: $string_hundreds = "seiscientos ";   break;
    case 7: $string_hundreds = "setecientos ";   break; // Special case
    case 8: $string_hundreds = "ochocientos ";   break;
    case 9: $string_hundreds = "novecientos ";   break; // Special case
} // end switch hundreds

// casgade trough tens. This will convert the tens part to corresponding
// strings in spanish. Note, however that the strings between 11 and 19
// are all special cases. Also 21-29 is a special case in spanish.
switch ($tens) {
    case 1: // Special case, depends on units for each conversion
            switch($units){
                case 1: $string_tens = "once";        break; // Special case
                case 2: $string_tens = "doce";        break; // Special case
                case 3: $string_tens = "trece";       break; // Special case
                case 4: $string_tens = "catorce";     break; // Special case
                case 5: $string_tens = "quince";      break; // Special case
                case 6: $string_tens = "dieciseis";   break; // Special case
                case 7: $string_tens = "diecisiete";  break; // Special case
                case 8: $string_tens = "dieciocho";   break; // Special case
                case 9: $string_tens = "diecinueve";  break; // Special case
            } break; // end switch special case tens/units

    case 2: $string_tens = "veinti";        break; // Special case
    case 3: $string_tens = "treinta";       break;
    case 4: $string_tens = "cuarenta";      break;
    case 5: $string_tens = "cincuenta";     break; 
    case 6: $string_tens = "sesenta";       break;
    case 7: $string_tens = "setenta";       break; 
    case 8: $string_tens = "ochenta";       break;
    case 9: $string_tens = "noventa";       break; 
} // end switch tens


// cascades trough units, This will convert the units part to corresponding
// strings in spanish. Note however that a check is being made to see wether
// the special cases 11-19 were used. In that case, the whole conversion of
// individual units is ignored since it was already made in the tens cascade. 

if ($tens == 1) {
        $string_units="";  // empties the units check, since it has alredy been handled on the tens switch
} else {
   switch ($units) {
       case 1: $string_units = "un";       break; 
       case 2: $string_units = "dos";      break;
       case 3: $string_units = "tres";     break;
       case 4: $string_units = "cuatro";   break;
       case 5: $string_units = "cinco";    break; 
       case 6: $string_units = "seis";     break;
       case 7: $string_units = "siete";    break; 
       case 8: $string_units = "ocho";     break;
       case 9: $string_units = "nueve";    break; 
   } // end switch units
} // end if-then-else


//final special cases. This conditions will handle the special cases which
//are not as general as the ones in the cascades. Basically four:

// when you've got 100, you dont' say 'ciento' you say 'cien'
// 'ciento' is used only for [101 >= number > 199]
if ($hundreds == 1 and $tens == 0 and $units == 0) {
        $string_hundreds = "cien" ;
} 

// when you've got 10, you don't say any of the 11-19 special 
// cases.. just say 'diez'
if ($tens == 1 and $units ==0) {
        $string_tens = "diez" ;
}

// when you've got 20, you don't say 'veinti', which is used
// only for [21 >= number > 29]
if ($tens == 2 and $units ==0) {
        $string_tens = "veinte" ;
}

// for numbers >= 30, you don't use a single word such as veintiuno
// (twenty one), you must add 'y' (and), and use two words. v.gr 31
// 'treinta y uno' (thirty and one)
if ($tens >=3 and $units >=1) {
        $string_tens = $string_tens." y ";
}

// this line gathers all the hundreds, tens and units into the final string
// and returns it as the function value. 
$final_string = $string_hundreds.$string_tens.$string_units;
return $final_string ;

} //end of function string_literal_conversion() ================================

/*==================== FIN CODEPESOS ===========================================*/
?>