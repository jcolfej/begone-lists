<?php

$files = [
  [
    'file' => __DIR__.'/allow-orangetelephone.xml',
    'type' => 'allow',
    'url'  => 'https://prod.odial.net/api/getPublicLrdList'
  ],
  [
    'file' => __DIR__.'/blacklist-orangetelephone.xml',
    'type' => 'blacklist',
    'url'  => 'https://prod.odial.net/api/getPublicSpamTopList'
  ]
];

$params = [
  'token' => 'Zet4dSze%T!z',
  'appName' => 'dialler',
  'simMccMnc' => '20801',
];





function getData($url, $params = []) {
  $curl = curl_init();
  curl_setopt($curl, CURLOPT_URL, $url.'?'.http_build_query($params));
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
  $data = curl_exec($curl);
  curl_close($curl);

  if ($data) {
    $data = gzdecode($data);
    $data = json_decode($data, true);
    return $data;
  }
  
  return null;
}





foreach ($files as $file) {
  echo PHP_EOL;
  echo 'Update '.$file['file'].PHP_EOL;

  $list = [];

  if (file_exists($file['file'])) {
    $xml = simplexml_load_file($file['file']);
    
    foreach ($xml->array->dict as $dict) {
      $current = array_combine((array) $dict->key, (array) $dict->string);
      $list[$current['number']] = $current;
    }
  }

  echo ' > Currently : '.count($list).' numbers...'.PHP_EOL;

  $data = getData($file['url'], $params);

  foreach ($data['result'] as $d) {
    if ($file['type'] === 'allow') {
      $name = !empty($d['reason']) ? $d['name'].' - '.$d['reason'] : $d['name'];
    } else {
      $name = $d['mainSpamType'];
    }

    $list[$d['number']] = [
      'addNational' => 'true',
      'category'    => ($file['type'] === 'allow' ? '1' : '0'),
      'number'      => $d['number'],
      'title'       => 'OrangeTelephone - '.$name
    ];
  }

  echo ' > Now : '.count($list).' numbers!'.PHP_EOL;

  echo ' > Build XML file...'.PHP_EOL;

  ksort($list);
  
  file_put_contents($file['file'], <<<'XML'
  <?xml version="1.0" encoding="UTF-8"?>
  <!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
  <plist version="1.0">
    <array>
  
  XML);

  foreach ($list as $c) {
    file_put_contents($file['file'], <<<XML
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

  file_put_contents($file['file'], <<<'XML'
    </array>
  </plist>
  XML, FILE_APPEND);
}



die();











$allowURL = 'https://prod.odial.net/api/getPublicLrdList';
$blacklistURL = 'https://prod.odial.net/api/getPublicSpamTopList';











$data = getData($allowURL, $params);

file_put_contents('allow-orangetelephone.xml', <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
<plist version="1.0">
  <array>

XML);

foreach ($data['result'] as $d) {
  $name = !empty($d['reason']) ? $d['name'].' - '.$d['reason'] : $d['name'];

  file_put_contents('allow-orangetelephone.xml', <<<XML
      <dict>
        <key>addNational</key>
        <string>true</string>
        <key>category</key>
        <string>1</string>
        <key>number</key>
        <string>{$d['number']}</string>
        <key>title</key>
        <string>OrangeTelephone - {$name}</string>
      </dict>

  XML, FILE_APPEND);
}

file_put_contents('allow-orangetelephone.xml', <<<'XML'
  </array>
</plist>
XML, FILE_APPEND);





$data = getData($blacklistURL, $params);

file_put_contents('blacklist-orangetelephone.xml', <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
<plist version="1.0">
  <array>

XML);

foreach ($data['result'] as $d) {
  file_put_contents('blacklist-orangetelephone.xml', <<<XML
      <dict>
        <key>addNational</key>
        <string>true</string>
        <key>category</key>
        <string>0</string>
        <key>number</key>
        <string>{$d['number']}</string>
        <key>title</key>
        <string>OrangeTelephone - {$d['mainSpamType']}</string>
      </dict>

  XML, FILE_APPEND);
}

file_put_contents('blacklist-orangetelephone.xml', <<<'XML'
  </array>
</plist>
XML, FILE_APPEND);