<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class TaskController extends AbstractController
{
    /**
     * @Route("/gettasks")
     */
    public function gettasks(): Response
    {
        if ($_SERVER['REQUEST_METHOD'] == "POST") {
            return new Response(file_get_contents($_SERVER["DOCUMENT_ROOT"] . "/data/tasks.json"));
        }
    }

    public function getAll(){
        return json_decode(file_get_contents($_SERVER["DOCUMENT_ROOT"] . "/data/tasks.json"), true);
    }
    public function save($tasks)
    {
        file_put_contents($_SERVER["DOCUMENT_ROOT"] . "/data/tasks.json", json_encode($tasks));
    }
    //возвращает ключ задачи
    public function getById($id)
    {
        $k = false;
        $tasks = $this->getAll();
        foreach ($tasks as $key => $task) {
            if (array_search($id, $task) !== false) {
                $k = $key;
                break;
            }
        }
        return $k;
    }

    public function saveFiles()
    {
        $files = [];
        if (isset($_FILES['images'])) {
            foreach ($_FILES['images'] as $key => $value) {
                foreach ($value as $k => $v) {
                    $_FILES['images'][$k][$key] = $v;
                }
                unset($_FILES['images'][$key]);
            }
            foreach ($_FILES['images'] as $k => $v) {
                $fileName = $_FILES['images'][$k]['name'];
                $fileTmpName = $_FILES['images'][$k]['tmp_name'];
                $fileType = $_FILES['images'][$k]['type'];
                $fileSize = $_FILES['images'][$k]['size'];
                $errorCode = $_FILES['images'][$k]['error'];

                // Проверим на ошибки
                if ($errorCode !== UPLOAD_ERR_OK || !is_uploaded_file($fileTmpName)) {
                    $errorMessages = [
                        UPLOAD_ERR_INI_SIZE   => 'Размер файла превысил значение upload_max_filesize в конфигурации PHP.',
                        UPLOAD_ERR_FORM_SIZE  => 'Размер загружаемого файла превысил значение MAX_FILE_SIZE в HTML-форме.',
                        UPLOAD_ERR_PARTIAL    => 'Загружаемый файл был получен только частично.',
                        UPLOAD_ERR_NO_FILE    => 'Файл не был загружен.',
                        UPLOAD_ERR_NO_TMP_DIR => 'Отсутствует временная папка.',
                        UPLOAD_ERR_CANT_WRITE => 'Не удалось записать файл на диск.',
                        UPLOAD_ERR_EXTENSION  => 'PHP-расширение остановило загрузку файла.',
                    ];
                    $unknownMessage = 'При загрузке файла произошла неизвестная ошибка.';
                    $outputMessage = isset($errorMessages[$errorCode]) ? $errorMessages[$errorCode] : $unknownMessage;
                    // Выведем название ошибки
                    if ($outputMessage == "Файл не был загружен.") {
                        return false;
                    } else {
                        die($outputMessage);
                    }
                } else {
                    $fi = finfo_open(FILEINFO_MIME_TYPE);
                    $mime = (string) finfo_file($fi, $fileTmpName);
                    if (strpos($mime, 'image') === false) die('Можно загружать только изображения.');
                    $image = getimagesize($fileTmpName);
                    $name = $this->getRandomFileName($fileTmpName);
                    $extension = image_type_to_extension($image[2]);
                    $format = str_replace('jpeg', 'jpg', $extension);
                    if (!move_uploaded_file($fileTmpName, $_SERVER["DOCUMENT_ROOT"] . '/assets/img/' . $name . $format)) {
                        die('При записи изображения на диск произошла ошибка.');
                    }
                    array_push($files, $name . $format);
                }
            };
            return $files;
        }
    }
    public function getRandomFileName($path)
    {
        $path = $path ? $path . '/' : '';
        do {
            $name = md5(microtime() . rand(0, 9999));
            $file = $path . $name;
        } while (file_exists($file));

        return $name;
    }
    /**
     * @Route("/create")
     */
    public function create(): Response
    {
        if ($_SERVER['REQUEST_METHOD'] == "GET") {
            return $this->render('task/create.html.twig');
        } elseif ($_SERVER['REQUEST_METHOD'] == "POST") {
            date_default_timezone_set("Asia/Irkutsk");
            $files = $this->saveFiles();
            $tasks = $this->getAll();
            $task["id"] = isset($tasks[0]) ? end($tasks)["id"] + 1 : 1;
            $task["title"] = $_POST["title"];
            $task["images"] = $files === false ? "[]" : json_encode($files);
            $task["text"] = $_POST["text"];
            $task["createtime"] = date("Y-m-d H:i:s");
            $task["updatetime"] = "";
            $task["status"] = "1";
            array_push($tasks, $task);
            $this->save($tasks);
            return $this->redirect("/");
        }
    }
    /**
     * @Route("/")
     */
    public function read(): Response
    {
        $tasks = $this->getAll();
        return $this->render('task/read.html.twig', ["tasks" => $tasks]);
    }

    /**
     * @Route("/update/{id}")
     */
    public function update($id): Response
    {
        if ($_SERVER['REQUEST_METHOD'] == "GET") {
            $tasks = $this->getAll();
            return $this->render('task/update.html.twig', ["task" => $tasks[$this->getById($id)], "images" => json_decode($tasks[$this->getById($id)]["images"])]);
        } elseif ($_SERVER['REQUEST_METHOD'] == "POST") {
            date_default_timezone_set("Asia/Irkutsk");
            $tasks = $this->getAll();
            $task = &$tasks[$this->getById($id)];
            $task["title"] = $_POST["title"];
            $task["status"] = $_POST["status"];
            $task["text"] = $_POST["text"];
            $task["updatetime"] = date("Y-m-d H:i:s");
            $this->save($tasks);
            return $this->redirect("/");
        }
    }



    /**
     * @Route("/sendfile/{id}", methods={"POST"})
     */
    public function sendfile($id): Response
    {
        if ($_SERVER['REQUEST_METHOD'] == "POST") {
            date_default_timezone_set("Asia/Irkutsk");
            $files = $this->saveFiles();
            $tasks = $this->getAll();
            $task = &$tasks[$this->getById($id)];
            $fd = json_decode($task["images"]);
            $files = array_merge($fd, $files);
            $task["images"] = $files === false ? "[]" : json_encode($files);
            $task["updatetime"] = date("Y-m-d H:i:s");
            $this->save($tasks);
            return new Response($task["images"]);
        }
    }

    /**
     * @Route("/delete/{id}")
     */
    public function dalete($id): Response
    {
        $tasks = $this->getAll();
        $files = json_decode($tasks[$this->getById($id)]["images"]);
        foreach ($files as $file) {
            unlink($_SERVER["DOCUMENT_ROOT"] . '/assets/img/' . $file);
        }
        unset($tasks[$this->getById($id)]);
        $this->save($tasks);
        return $this->redirect("/");
    }

    /**
     * @Route("/deleteimage/{id}/{fileid}")
     */
    public function deleteimage($id, $fileid): Response
    {
        $tasks = $this->getAll();
        $files = json_decode($tasks[$this->getById($id)]["images"]);
        unlink($_SERVER["DOCUMENT_ROOT"] . '/assets/img/' . $files[$fileid - 1]);
        array_splice($files, $fileid - 1, 1);
        $tasks[$this->getById($id)]["images"] = json_encode($files);
        $this->save($tasks);
        return $this->redirect("/update/" . $id);
    }
}
