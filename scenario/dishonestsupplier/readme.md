#откуда дровишки

Данные скачиваются с ftp сервера сервера госзакупки
`https://zakupki.gov.ru/`

Там ведется список недобросовестных поставщиков.
`ftp://free:free@ftp.zakupki.gov.ru/fcs_fas/`

В списке разного содержимого xml файлы, в структуре которых разобраться оказалось сложно и ненужно.
принято решение парсить xml как текст, выделяя ключевые параметры из тегов, которые в разных xml оказываются в разных местах.

История ведется с 14, вроде, года. Однако общая практика - исключение участника из 
списка по прошествии 2-х лет, так что читать файлы старще 3 лет бессмысленно.
 
