<?php

const FILENAME = 'adlar.txt';

function openFile() {
    if (!file_exists(FILENAME)) {
        $file = fopen(FILENAME, 'w+');

        if($file == false) {
            echo ("Fayl yaradıla bilmədi.");
            exit();
        }

        return $file;
    } else return fopen(FILENAME, 'r+');
}

function initRelativeNameList(string &$line, $file, string $letter) {
    $arr = [$line];

    while(true) {
        $line = trim(fgets($file));

        if($line != false) {
            if($line[0] === $letter) {
                $arr[] = $line;
            } else
                break;
        } else
            break;
    }

    return $arr;
}

function initNameList($file) {
    $names = [];
    $lowerCaseOrd = ord('a');
    $upperCaseOrd = ord('A');
    $line = trim(fgets($file));

    for($i = 0; $i < 26; $i++) {
        $lowerCaseLetter = chr($lowerCaseOrd + $i);
        $upperCaseLetter = chr($upperCaseOrd + $i);

        if($line != false &&
            ($line[0] === $lowerCaseLetter || $line[0] === $upperCaseLetter)) {


            if($line[0] == $lowerCaseLetter) {
                $names[$lowerCaseLetter] = initRelativeNameList($line, $file, $lowerCaseLetter);

                if (!array_key_exists($upperCaseLetter, $names))
                    $names[$upperCaseLetter] = [];
            } else {
                if (!array_key_exists($lowerCaseLetter, $names))
                    $names[$lowerCaseLetter] = [];

                $names[$upperCaseLetter] = initRelativeNameList($line, $file, $upperCaseLetter);
            }
            $i--;
        } else {
            if (!array_key_exists($lowerCaseLetter, $names))
                $names[$lowerCaseLetter] = [];
            if (!array_key_exists($upperCaseLetter, $names))
                $names[$upperCaseLetter] = [];
        }
    }

    return $names;
}

function getRelativeOffset(string $newName, array &$nameList): int {
    // $yeniAd həmin əlifba sırasına görə nisbi indeksini qaytarır
    // nisbi sıra dedikdə bütün $yeniAd'ın baş hərfi ilə başlayan adlar sırası

    if (count($nameList) === 0)
        return 0;

    foreach($nameList as $i => $name)
        if($newName < $name)
            return $i;

    return count($nameList);
}

function nameList($file) {
    static $names = null;

    if($names === null)
        $names = initNameList($file);
    
    return $names;
}

function updateFile($nameList) {
    $file = fopen(FILENAME, 'w');

    foreach ($nameList as $i) {
        foreach ($i as $j) {
            fwrite($file, $j.PHP_EOL);
        }
    }

    fclose($file);
}

function previousLetter($letter) {
    if ($letter === 'a') return 'a';
    else if(ctype_upper($letter)) {
        $prevLetter = strtolower($letter);
    } else {
        $prevLetter = strtoupper(chr(ord($letter) - 1));
    }

    return $prevLetter;
}

function getOffsetOfLetter(string $letter, array &$nameList): int {
    $offset = 0;

    if($letter === 'a') return count($nameList[$letter]);
    else {
        // Rekursiv geriyə sıralamayla gedib offset -ləri cəmləmək
        // Məsələn
        // B -> b
        // b -> A
        // A -> a

        $offset = count($nameList[$letter]);

        $offset += getOffsetOfLetter(previousLetter($letter), $nameList);
    }

    return $offset;
}


function orderRelatively(string $yeniAd, array &$nameList): int {
    $letter = $yeniAd[0];
    $relativeOffset = getRelativeOffset($yeniAd, $nameList[$letter]);
    array_splice($nameList[$letter], $relativeOffset, 0, $yeniAd);

    return $relativeOffset;
}

function &cachedNames($file) {
    // cached yəni hər dəfə initialize etməyə ehtiyac yoxdu
    // proqram başlayan zaman ilk dəfə ümumi sıra verilmiş fayldan qurulacaq
    // və hər dəfə yeni əlavə olan adlar yenidən fayldan oxunmağa ehticay qalmayacaq 

    static $names = null;
    
    if($names === null) {
        $names = nameList($file);
    }

    return $names;
}

function yeniAd(string $ad) {
    $file = openFile();
    $names = &cachedNames($file);

    $relativeOffset = orderRelatively($ad, $names);

    $alphabeticalOffset = getOffsetOfLetter(previousLetter($ad[0]), $names);
    $lineNumber = $relativeOffset + $alphabeticalOffset + 1;
    
    updateFile($names);
    fclose($file);

    return $lineNumber;
}
