<?php
 
echo '<option value="">Select</option>
 
<option value="Australia">Australia</option>
<option value="Austria">Austria</option>
<option value="Brazil">Brazil</option>
<option value="Canada">Canada</option>
<option value="China">China</option>
<option value="France">France</option>
<option value="Germany">Germany</option>
<option value="India">India</option>
<option value="Indonesia">Indonesia</option>
<option value="Italy">Italy</option>
<option value="Japan">Japan</option>
<option value="Malaysia">Malaysia</option>
<option value="Singapore">Singapore</option>
<option value="Switzerland">Switzerland</option>
<option value="Thailand">Thailand</option>
<option value="Turkey">Turkey</option>
<option value="UAE">United Arab Emirates</option>
<option value="UK">United Kingdom</option>
<option value="USA">United States of America</option>
<option value="Vietnam">Vietnam</option>


echo "<option value="">_____________</option>';
 
$country_list = array(
 
"Afghanistan",
 
"Albania",
 
"Algeria",
 
"Andorra",
 
"Angola",
 
"Antigua and Barbuda",
 
"Argentina",
 
"Armenia",
 
"Azerbaijan",
 
"Bahamas",
 
"Bahrain",
 
"Bangladesh",
 
"Barbados",
 
"Belarus",
 
"Belgium",
 
"Belize",
 
"Benin",
 
"Bhutan",
 
"Bolivia",
 
"Bosnia and Herzegovina",
 
"Botswana",
 
"Brunei",
 
"Bulgaria",
 
"Burkina Faso",
 
"Burundi",
 
"Cambodia",
 
"Cameroon",
 
"Cape Verde",
 
"Central African Republic",
 
"Chad",
 
"Chile",
 
"Colombi",
 
"Comoros",
 
"Congo (Brazzaville)",
 
"Congo",
 
"Costa Rica",
 
"Cote d'Ivoire",
 
"Croatia",
 
"Cuba",
 
"Cyprus",
 
"Czech Republic",
 
"Denmark",
 
"Djibouti",
 
"Dominica",
 
"Dominican Republic",
 
"East Timor (Timor Timur)",
 
"Ecuador",
 
"Egypt",
 
"El Salvador",
 
"Equatorial Guinea",
 
"Eritrea",
 
"Estonia",
 
"Ethiopia",
 
"Fiji",
 
"Finland",
 
"Gabon",
 
"Gambia",
 
"Georgia",
 
"Ghana",
 
"Greece",
 
"Grenada",
 
"Guatemala",
 
"Guinea",
 
"Guinea-Bissau",
 
"Guyana",
 
"Haiti",
 
"Honduras",
 
"Hungary",
 
"Iceland",
 
"Iran",
 
"Iraq",
 
"Ireland",
 
"Israel",
 
"Jamaica",
 
"Jordan",
 
"Kazakhstan",
 
"Kenya",
 
"Kiribati",
 
"Korea, North",
 
"Korea, South",
 
"Kuwait",
 
"Kyrgyzstan",
 
"Laos",
 
"Latvia",
 
"Lebanon",
 
"Lesotho",
 
"Liberia",
 
"Libya",
 
"Liechtenstein",
 
"Lithuania",
 
"Luxembourg",
 
"Macedonia",
 
"Madagascar",
 
"Malawi",
 
"Maldives",
 
"Mali",
 
"Malta",
 
"Marshall Islands",
 
"Mauritania",
 
"Mauritius",
 
"Mexico",
 
"Micronesia",
 
"Moldova",
 
"Monaco",
 
"Mongolia",
 
"Morocco",
 
"Mozambique",
 
"Myanmar",
 
"Namibia",
 
"Nauru",
 
"Nepa",
 
"Netherlands",
 
"New Zealand",
 
"Nicaragua",
 
"Niger",
 
"Nigeria",
 
"Norway",
 
"Oman",
 
"Pakistan",
 
"Palau",
 
"Panama",
 
"Papua New Guinea",
 
"Paraguay",
 
"Peru",
 
"Philippines",
 
"Poland",
 
"Portugal",
 
"Qatar",
 
"Romania",
 
"Russia",
 
"Rwanda",
 
"Saint Kitts and Nevis",
 
"Saint Lucia",
 
"Saint Vincent",
 
"Samoa",
 
"San Marino",
 
"Sao Tome and Principe",
 
"Saudi Arabia",
 
"Senegal",
 
"Serbia and Montenegro",
 
"Seychelles",
 
"Sierra Leone",

"Slovakia",
 
"Slovenia",
 
"Solomon Islands",
 
"Somalia",
 
"South Africa",
 
"Spain",
 
"Sri Lanka",
 
"Sudan",
 
"Suriname",
 
"Swaziland",
 
"Sweden",
  
"Syria",
 
"Taiwan",
 
"Tajikistan",
 
"Tanzania",
 
"Togo",
 
"Tonga",
 
"Trinidad and Tobago",
 
"Tunisia",
 
"Turkmenistan",
 
"Tuvalu",
 
"Uganda",
 
"Ukraine",
 
"Uruguay",
 
"Uzbekistan",
 
"Vanuatu",
 

"Venezuela",
 
"Yemen",
 
"Zambia",
 
"Zimbabwe"
 
);
 
foreach ($country_list as $country) {
 
echo "<option value='$country'";
 
if (isset($_POST['country']) && $_POST['country']==$country) {
 
echo " selected='selected'";
 
} 
 
echo ">$country</option>";
 
}
 
?>
