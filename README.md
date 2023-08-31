# MyTracker - Аналитика от VK для Joomla

Плагин под CMS Joomla 4, Joomla 5 для интеграции мультиплатформенной системы аналитики и атрибуции для мобильных приложений MyTracker и сайтов.

![MyTracker - Аналитика от VK для Joomla](https://repository-images.githubusercontent.com/685085738/9d38ddc0-20a4-48de-a83f-a52f70c4b1c7)

## Разработка и установка из исходников

1. Склонируйте репозиторий к себе на локальный компьютер:

```bash
git clone git@github.com:mihdan/mytracker-joomla.git
```

2. Перейдите в папку со склонированным проектом:

```bash
cd mytracker-joomla
```

3. Установите зависимости Composer (phpcs, psalm):

```bash
composer install
```

4. Выйдите на уровень выше:

```bash
cd ../
```

5. Упакуйте файлы в zip-архив:

```bash
zip -r -9 ./mytracker-joomla/*
```

5. Установите полученный архив с плагином через админку Joomla.
