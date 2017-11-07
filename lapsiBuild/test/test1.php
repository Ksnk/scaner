<?php
//$headers = array();
/*foreach ($_SERVER as $key => $value) {
    if (substr($key, 0, 5) == "HTTP_") {
        $key = str_replace(" ", "-", ucwords(strtolower(str_replace("_", " ", substr($key, 5)))));
        $headers[$key] = $value;
    } else {
        $headers[$key] = $value;
    }
}*/
$headers = apache_request_headers();     // �������� ��� ��������� �������
if (!isset($headers['Authorization'])) { // ���� ��������� ����������� ���
    header('HTTP/1.1 401 Unauthorized'); // ������� �� ������� �����������
    header('WWW-Authenticate: NTLM'); // ��� ��������� ����������� - NTLM
    exit; // ��������� ���������� �������
}
// ��������� ����������� �� ������� ������
if (substr($headers['Authorization'], 0, 5) == 'NTLM ') { // ���������, ��� ��� NTLM-��������������

    $chain = base64_decode(substr($headers['Authorization'], 5)); // �������� �������������� ��������
    //Authorization:NTLM TlRMTVNTUAABAAAAB4IIogAAAAAAAAAAAAAAAAAAAAAGAbEdAAAADw==

    switch (ord($chain{8})) { // ������� ����� ����� �������� �������������
        case 3: // ���� 5 - ���� ��������� type-3
            foreach (array('xxx','LM_resp', 'NT_resp', 'domain', 'user', 'host','yyy') as $k => $v) {
               // echo 'debug:'.hex_dump(substr($chain, $k * 8 + 6, 8)). "<br>\r\n";
                extract(unpack('vlength/voffset', substr($chain, $k * 8 + 14, 4)));
                $val = substr($chain, $offset, $length);
                echo "$v: " .($k < 2 ? hex_dump($val) : (iconv('UTF-16LE', 'cp1251', $val) .
                    ' !! '.hex_dump($val)))."<br>\r\n";
            }
            echo "total:".hex_dump($chain). "<br>\r\n";
            exit;
        case 1: // ���� 3 (��� ���� == 0xB2, � �������� �� 130). 178 -> B2 ��� 130 -> 82
// 0x82 ���������� ������� � ����� ��� ������� ����� ������, � 0xB2 ���������� IE ��� ��������� "����������� ������� ����� � ������"
            if (ord($chain{13}) == 0x82 || ord($chain{13}) == 0xB2) { // ��������� ������� NTLM 0x82 �� �������� 13 � ��������� type-1:
                $chain = "NTLMSSP\x00" . // ��������
                    "\x02" /* ����� ����� */ . "\x00\x00\x00\x00\x00\x00\x00" .
                    "\x28\x00" /* ����� ����� ��������� */ . "\x00\x00" .
                    "\x01\x82" /* ������� */ . "\x00\x00" .
                    "\x00\x02\x02\x02\x00\x00\x00\x00" . // nonce
                    "\x00\x00\x00\x00\x00\x00\x00\x00";
                header('HTTP/1.1 401 Unauthorized');
                header('WWW-Authenticate: NTLM ' . base64_encode($chain)); // ���������� ��������� type-2
                exit;
            }
    }
}

function hex_dump($str)
{ // ��������������� �������, ���������� ����������������� ���� ������
    return substr(preg_replace('#.#se', 'sprintf("%02x ",ord("$0"))', $str), 0, -1).
        ' | '.preg_replace('#.#se', '(ord("$0")<32?".":"$0")', $str);
}

