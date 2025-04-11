<?php

$files = [
  [
    'file'  => __DIR__.'/../blacklist/blacklist-datagouv-onoff.xml',
    'name'  => 'OnOff',
    'asn'   => 'ONOF'
  ],
  [
    'file'  => __DIR__.'/../blacklist/blacklist-datagouv-ubicentrex.xml',
    'name'  => 'Ubicentrex',
    'asn'   => 'UBIC'
  ],
  [
    'file'  => __DIR__.'/../blacklist/blacklist-datagouv-lyca.xml',
    'name'  => 'Lyca',
    'asn'   => 'LYCA'
  ],
  [
    'file'  => __DIR__.'/../blacklist/blacklist-datagouv-aircall.xml',
    'name'  => 'Aircall',
    'asn'   => 'AIRC'
  ],
  [
    'file'  => __DIR__.'/../blacklist/blacklist-datagouv-bjt.xml',
    'name'  => 'BJT Partners',
    'asn'   => 'BJTP'
  ],
  [
    'file'  => __DIR__.'/../blacklist/blacklist-datagouv-kavkom.xml',
    'name'  => 'Kavkom',
    'asn'   => 'KAVE'
  ],
  [
    'file'  => __DIR__.'/../blacklist/blacklist-datagouv-destiny.xml',
    'name'  => 'Destiny',
    'asn'   => 'OPEN'
  ]
];





function getData($url, $params = []) {
  $curl = curl_init();
  curl_setopt($curl, CURLOPT_URL, $url.'?'.http_build_query($params));
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
  $data = curl_exec($curl);
  curl_close($curl);

  if ($data) {
    $data = json_decode($data, true);
    return $data;
  }
  
  return null;
}

function extractCommonBeginning($start, $end) {
  $commonLength = 0;

  for ($i = 0; $i < min(strlen($start), strlen($end)); $i++) {
      if ($start[$i] !== $end[$i]) {
          break;
      }

      $commonLength++;
  }

  $commonPart = substr($start, 0, $commonLength);
  $formatted = $commonPart.str_repeat('#', strlen($start) - $commonLength);

  return (strpos($formatted, '0') === 0) ? '+33'.substr($formatted, 1) : $formatted;
}





foreach ($files as $file) {
  echo PHP_EOL;
  echo 'Update '.$file['file'].PHP_EOL;

  $list = [];
  $patch = 0;

  if (!is_dir(dirname($file['file']))) {
    mkdir(dirname($file['file']), 0777, true);
  }

  if (file_exists($file['file'])) {
    $xml = simplexml_load_file($file['file']);
    
    foreach ($xml->array->dict as $dict) {
      $current = array_combine((array) $dict->key, (array) $dict->string);
      $list[$current['number']] = $current;
    }
  }

  echo ' > Currently : '.count($list).' numbers...'.PHP_EOL;

  $url = 'https://tabular-api.data.gouv.fr/api/resources/90e8bdd0-0f5c-47ac-bd39-5f46463eb806/data/';
  $params = [
    'Mnémo__exact'  =>  $file['asn'],
    'page'          =>  1,
    'page_size'     =>  20,
  ];

  while (true) {
    $currentUrl = $url.'?'.http_build_query($params);

    echo ' > Fetching '.$currentUrl.'...'.PHP_EOL;
    $data = getData($url, $params);

    foreach ($data['data'] as $d) {
      $number = extractCommonBeginning($d['Tranche_Debut'], $d['Tranche_Fin']);

      $begoneData = [
        'addNational' => 'true',
        'category'    => '0',
        'number'      => $number,
        'title'       => 'Data Gouv - '.$file['name']
      ];

      if (!isset($list[$number]) || sha1(serialize($list[$number])) !== sha1(serialize($begoneData))) {
        $patch++;
      }

      $list[$number] = $begoneData;
    }

    if (isset($data['links']['next']) && !empty($data['links']['next'])) {
      $params['page']++;
    } else {
      break;
    }
  }

  echo ' > Now : '.count($list).' numbers!'.PHP_EOL;
  echo ' > Patch : '.$patch.' numbers!'.PHP_EOL;

  ksort($list);

  echo ' > Build XML file...'.PHP_EOL;
  
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
