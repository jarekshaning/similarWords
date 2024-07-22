<!DOCTYPE html>
<?php
        $defaultPercent = 70;
        $defaultDistance = 30;
?>
<html lang="pl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Tropiciel powtórzeń</title>
        <style>
            textarea {
                padding: 25px;
                height: 400px;
                overflow: auto;
                width: 80%;
            }
            div {
                width:95%;
                line-height: 1.7rem;
            }
        </style>
    </head>
    <body>
        <!-- Zdanie wprowadzone przez użytkownika -->
        <div>
            <form method="POST">
                <p><label for="percent">Podaj stopień podobieństwa słów (domyślnie <?php echo $defaultPercent;?>%):</label> <input type="text" name="percent" id="percent" size="2">%</p>
                <p><label for="lang">Wybierz typ języka:</label>
                    <select name="lang" id="lang">
                        <option value="pl">Języki ze spacjami (np. język polski)</option> 
                        <option value="zh">Języki chińskie</option>                 
                    </select></p>
                <p><label for="distance">Podaj maksymalną odległość między słowami podobnymi (domyślnie <?php echo $defaultDistance;?>):</label> <input type="text" name="distance" id="distance" size="2"></p>
                <p><label for="feed">Wprowadź tekst:</label></p>
                <p><textarea name="feed" id="feed" rows="1"></textarea></p>
                <p><input type="submit" value="Prześlij tekst"></p>
         
            </form>
        </div>
        <?php
        
// Disable error reporting in production
ini_set('display_errors', 0);
error_reporting(0);

function cjkToUnicode($input) {
    $unicode = '';
    for ($i = 0; $i < mb_strlen($input, 'UTF-8'); $i++) {
        $char = mb_substr($input, $i, 1, 'UTF-8');
        $code = unpack('H*', mb_convert_encoding($char, 'UCS-2BE', 'UTF-8'));
        $unicode .= ' \\u' . str_pad($code[1], 4, '0', STR_PAD_LEFT);
    }
    return $unicode;
}


function unicodeToCjk($unicode) {
    $pattern = '/\\\\u([0-9a-fA-F]{4})/';
    $result = preg_replace_callback($pattern, function ($matches) {
        return mb_convert_encoding(pack('H*', $matches[1]), 'UTF-8', 'UCS-2BE');
    }, $unicode);

    return $result;
}





        function makeInputSafeAgain($String)
        {   
            $replaced = array("'",  " \"",    "\" ",  "\".",  "\",",  "\"!",  "\"?",    "\"");
            $replacer = array("’",  " „",     "” ",   "” ",   "” ",   "”!",   "”?",     "”");            
            return htmlspecialchars(str_replace($replaced,$replacer,strip_tags($String)), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }
       
            // tagi drugiego słowa znalezionej pary
            $tagstart = '<span style="color:red;font-weight:bold">';
            $tagend = '</span>';

            //tagi pierwszego słowa znalezionej pary
            $tagstart1 = '<span style="color:red;font-style:italic">';
            $tagend1 = '</span>';

            $tagEndOfLine = " </li><li> ";
             
        // Sprawdź, czy zostało przesłane zdanie
        if (isset($_POST['feed'])) {


           
            $Percent = makeInputSafeAgain($_POST['percent']);
            $Distance = makeInputSafeAgain($_POST['distance']);
            $Lang = makeInputSafeAgain($_POST['lang']);        
            $Feed = makeInputSafeAgain($_POST['feed']);

            // procent podobieństwa
        
            if ($Lang == "zh") {
                $setPercent = 100;
                  $originalPercent = "dla języków chińskich wartość wynosi zawsze 100%";
            } elseif ($Percent == ""){
                $setPercent = $defaultPercent;
                $originalPercent = $setPercent."%";
            } else {
                $setPercent = str_replace(",", ".", $Percent);
                if (is_numeric($setPercent)) {
                    $originalPercent = $Percent."%";                      
                } else {
                    $setPercent = $defaultPercent;
                    $originalPercent = $setPercent . "% – wartość domyślna, gdyż nie wpisano liczby.";
                }

            }
            echo "<p>Stopień podobieństwa słów: " . $originalPercent . "</p>";

            // odległość maksymalna między wyrazami porównywanymi
        
            if ($Distance == "") {
                $wordDistance = $defaultDistance;
                $wordDistanceInfo = $wordDistance;
            } else {
                $wordDistance = str_replace(",", ".", $Distance);
                if (is_numeric($wordDistance)) {
                    $wordDistance = round($wordDistance);
                    $wordDistanceInfo = $wordDistance;
                } else {
                    $wordDistance = $defaultDistance;
                    $wordDistanceInfo = $wordDistance . " – wartość domyślna, gdyż nie wpisano liczby.";
                }
            }
            echo "<p>Maksymalna odległość między słowami podobnymi: " . $wordDistanceInfo . "</p>";
  
            ?>
            <p>Legenda:</p>
            <ul>
                <li><span style='color:red;font-style:italic'>Pierwsze słowo listy słów podobnych</span></li>
                <li><span style='color:red;font-weight:bold'>Ostatnie słowo listy słów podobnych</span></li>
                <li><span style='color:red;font-style:italic;font-weight:bold'>Środkowe słowa listy słów podobnych</span></li>
            </ul>
                 
            <?php
                
            // Podziel tekst na wyrazy, używając spacji jako separatora
       
            if($Lang == "zh"){
            
            $String = cjkToUnicode($Feed);
            $text = explode(" ", $String);
       
    

            } else {  
                $text = explode(" ", $Feed);
            }

           

       

            // tu zaczyna się algorytm
        
            //Duplikuj macierz: $text to macierz najpierw przeszukiwana, $Text to macierz później wyświetlana
            $Text = $text;
  
            $textLength = count($text);
        

            for ($x = 0; $x <= $textLength; $x++) {


                // usuwanie znaków przystankowych
              $text[$x] = str_replace(['°','†','‡','．','﹃','﹄','﹁','﹂','［','］','§','〔','〕','【','】','÷','×','®','™','©','′','″','‴','«','»','⟨','⟩','『','』','「','」','~','～',' ','%','/',',','!','?','"','\'',';',':','(',')','[',']','{','}','·','•','.','…','⋯','。','¡','¿','„','”','“','‰','‱','-','*','+','=','<','>','%','@','&','$','^','，','、','？','！','“','”','《','‒','–','—','―','》','〈','〉','…','￥','：','；','‘','’','（','）','\u00b0','\u2020','\u2021','\uff0e','\ufe43','\ufe44','\ufe41','\ufe42','\uff3b','\uff3d','\u00a7','\u3014','\u3015','\u3010','\u3011','\u00f7','\u00d7','\u00ae','\u2122','\u00a9','\u2032','\u2033','\u2034','\u00ab','\u00bb','\u27e8','\u27e9','\u300e','\u300f','\u300c','\u300d','\u007e','\uff5e','\u0020','\u0025','\u005c','\u002f','\u005c','\u005c','\u002c','\u005c','\u0021','\u005c','\u003f','\u005c','\u201d','\u005c','\u2019','\u003b','\u003a','\u005c','\u0028','\u005c','\u0029','\u005c','\u005b','\u005c','\u005d','\u005c','\u007b','\u005c','\u007d','\u00b7','\u2022','\u002e','\u2026','\u22ef','\u3002','\u00a1','\u00bf','\u201e','\u201d','\u201c','\u2030','\u2031','\u005c','\u002d','\u005c','\u002a','\u005c','\u002b','\u003d','\u005c','\u0025','\u0040','\u0026','\u0061','\u006d','\u0070','\u003b','\u005c','\u0024','\u005c','\u005e','\uff0c','\u3001','\uff1f','\uff01','\u201c','\u201d','\u300a','\u2012','\u2013','\u2014','\u2015','\u300b','\u3008','\u3009','\u2026','\u2026','\uffe5','\uff1a','\uff1b','\u2018','\u2019','\uff08','\uff09'], "", $text[$x]);

            }

            // przeszukaj macierz $text i wprowadź zmiany w macierzy $Text na podstawie wyników z macierzy $text
            $similarityCounter = 0;
            for ($i = 0; $i < $textLength; $i++) {

                // odstęp między słowami
        
                for ($j = 1; $j <= $wordDistance; $j++) {

                    //porównanie
            
                        similar_text($text[$i], $text[$i + $j], $percent);
                        
             
                    //wyróżnienie elementów macierzy $Text na podstawie podobieństwa elementów macierzy $text
                  
                    if ($percent >= $setPercent) {

                        $Text[$i] = $tagstart1 . $Text[$i] . $tagend1;
                        $Text[$i + $j] = $tagstart . $Text[$i + $j] . $tagend;
                        $similarityCounter++;
                        

                    }

                }
            }
            
            echo "<p>Liczba podobieństw: ".$similarityCounter."</p>";
            echo "<p>Długość tekstu w słowach: ".($textLength-1)."</p>";
            $similarityValue = strval(round(100*(($similarityCounter)/($textLength-1)),2));
            echo "<p>Monotonność tekstu: ".str_replace(".",",",$similarityValue)."%</p>";
            
                    // pokaż macierz $Text po zmianach
                        
                    ?>
                
                    <ol>
                    <li>
                    <?php
                    $Text = str_replace(["\n","\u000a"], "\n ".$tagEndOfLine, $Text);
                    if($Lang == "zh"){
                        $Text = unicodeToCjk($Text);
                        echo implode("", $Text);  
                                   
                    } else {
                        echo implode(" ", $Text);
                        
                    }
            ?>
                    </li>
                </ol>
        <?php
     
        }
        ?>
    </body>
</html>
