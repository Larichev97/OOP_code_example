<?php


namespace app\services\online_course\letter_templates;


interface LetterTemplateInterface
{
    public function viewTemplate(); // данные для шаблона письма
    public function sendLetter(); // отправка письма мейлером
    public function getCourse(); // Модель конкретного Курса
    public function getSendLetterFrom(); // почта, с которой отсылается письмо (почта компании)
    public function getSendLetterTo(); // отправка на почту компании
    public function getSubjectLetter(); // тема письма
    public function getLetterHtmlBody(); // "body" письма (5 разных шаблонов)
    public function getLetterFiles(); // файлы для письма
}
