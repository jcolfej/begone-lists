<?php

if (count($argv) !== 4) {
  echo 'Usage: php '.basename(__FILE__).' <addNational> <number> <title>'.PHP_EOL;
  exit(1);
}

$addNational = $argv[1];
$number = $argv[2];
$title = $argv[3];

if (!in_array($addNational, ['true', 'false'])) {
  echo ' > addNational must be "true" or "false"'.PHP_EOL;
  exit(1);
}

$file = __DIR__.'/../blacklist/blacklist-perso.xml';
$list = [];

if (file_exists($file)) {
  $xml = simplexml_load_file($file);
  
  foreach ($xml->array->dict as $dict) {
    $current = array_combine((array) $dict->key, (array) $dict->string);
    $list[$current['number']] = $current;
  }

  echo ' > '.count($list).' numbers loaded !'.PHP_EOL;
}

$list[$number] = [
  'addNational' => $addNational,
  'category'    => '0',
  'number'      => $number,
  'title'       => $title
];

ksort($list);

echo ' > Build XML file...'.PHP_EOL;

file_put_contents($file, <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
<plist version="1.0">
  <array>

XML);

foreach ($list as $c) {
  file_put_contents($file, <<<XML
      <dict>
        <key>addNational</key>
        <string>{$c['addNational']}</string>
        <key>category</key>
        <string>{$c['category']}</string>
        <key>number</key>
        <string>{$c['number']}</string>
        <key>title</key>
        <string>{$c['title']}</string>
      </dict>

  XML, FILE_APPEND);
}

file_put_contents($file, <<<'XML'
  </array>
</plist>
XML, FILE_APPEND);
