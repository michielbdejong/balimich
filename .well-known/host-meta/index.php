<?php
header('Access-Control-Max-Age: 86400');
header('Content-Type: text/html');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Max-Age: 86400');
?>
<\?xml version='1.0' encoding='UTF-8'?>
<XRD xmlns='http://docs.oasis-open.org/ns/xri/xrd-1.0'
     xmlns:hm='http://host-meta.net/xrd/1.0'>
 
    <hm:Host><?php echo $_SERVER['HTTP_HOST']?></hm:Host>
 
    <Link rel='lrdd'
          template='<?php echo $_SERVER['HTTP_HOST']?>/WebFinger/?uri={uri}'>
        <Title>Resource Descriptor</Title>
    </Link>
</XRD>
