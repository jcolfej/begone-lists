<?php

$allowURL = 'https://prod.odial.net/api/getPublicLrdList';
$blacklistURL = 'https://prod.odial.net/api/getPublicSpamTopList';

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