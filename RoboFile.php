<?php
/**
 * This is project's console commands configuration for Robo task runner.
 *
 * @see http://robo.li/
 */
use Symfony\Component\Finder\Finder;
use Symfony\Component\Filesystem\Filesystem;

class RoboFile extends \Robo\Tasks
{
    private $newdir;
    private $suffix = '1.0';
    private $files;
    private $changes = array(
        'Router::toAction(' => 'Redirect::toAction(',
        'Router::to(' => 'Redirect::to(',
        'Router::route_to(' => 'Redirect::intern(',
        'Flash::notice(' => 'Flash::info(',
        'Flash::success(' => 'Flash::valid(',
        'Util::uncamelize(' => 'Util::smallcase(',
        "View::response('view')" => "View::template(null)"
    );
    private $delete = array();
    // define public methods as commands
    /**
    * @description Convertir una app beta2-0.9 a 1.0
    */
    public function kumbiaUpdate() {
        $this->say('<info>Actualizando aplicación de KumbiaPHP a v1.0</info>');
        $this->newdir = __DIR__.$this->suffix;
        $exist = file_exists($this->newdir) ? 'Existe':'No existe';
        $this->say($exist);
        if($exist === 'No existe') {
            $this->say('<info>Copiando</info> '.__DIR__.' en '.$this->newdir);
            $this->_copyDir(__DIR__, $this->newdir);
        }
        //$this->dir($this->newdir);
        $this->files();
        $this->kumbiaChanges();
    }
    private function kumbiaChanges() {
        foreach($this->changes as $from=>$to) {
            $this->say("Cambiando $from a $to");
            foreach($this->files as $file) {
                $this->taskReplaceInFile($file)
                     ->from($from)
                     ->to($to)
                     ->run();
            }
        }
    }
    private function kumbiaDelete() {
        $this->say('Eliminando Router::to() a Redirect::to()');
        foreach($this->files as $file) {
            $this->taskReplaceInFile($file)
                 ->from('Router::toAction')
                 ->to('Redirect::toAction')
                 ->run();
        }
    }
    private function files($dir = '/app', $extension = '*.php') {
        $this->files = Finder::create()
            ->name($extension)
            ->in($this->newdir.$dir);
        $this->say(count($this->files).' ficheros');
    }
    /**
    * @description Borrar la cache de la applicación
    */
    public function kumbiaCacheClean() {
        $this->_cleanDir('app/temp/cache');
        $this->say('<info>Cache borrada.</info>');
    }
    /**
    * @description Actualiza <?php echo a <?= (PHP 5.4+)
    */
    public function kumbiaEchoShort($dir = 'app/views', $extension = "*.phtml"){
      $this->say('<info>actualizando <?php echo a <?=</info>');
      $this->files($dir, $extension);
      foreach ($this->files as $file) {
          $this->taskReplaceInFile($file->getRealPath())
          ->from('<?php echo ')
          ->to('<?= ')
          ->run();
      }
      $this->say('<info>echo actualizado a php 5.4.</info>');
    }
    /**
    * @description Crea un controlador sencillo y su carpeta de vistas
    *
    * @param string $controllerName Nombre del controlador
    */
    public function kumbiaCreateController($controllerName) {
      $file = "app/controllers/{$controllerName}_controller.php";
      $viewsDir = "app/views/{$controllerName}";
      $fs = new Filesystem();
      if (!$fs->exists($file)) {
        $this->say("<info>Creando Controlador</info>");
        //crear archivo
        $fs->touch($file);
        //escribir template
        $controllerName = ucfirst($controllerName);
        $this->taskWriteToFile($file)
           ->line("<?php")
           ->line("\tclass {$controllerName}Controller extends AppController {")
           ->line("\t\t")
           ->line("\t}")
           ->run();
        //crear directorio para las vistas
        $this->say("<info>Controlador creado en {$file}</info>");

      } else {
        $this->say("<error>Controlador ya existía en {$file}</error>");
      }
      if (!$fs->exists($viewsDir)) {
        $fs->mkdir($viewsDir);
        $this->say("<info>Carpeta de Vistas creada en {$viewsDir}</info>");
      } else {
        $this->say("<error>Carpeta de Vistas ya existía en {$viewsDir}</error>");
      }
    }

  /**
   * @description Crea un modelo, por defecto de ActiveRecord
   *
   * @param string $modelName Nombre del modelo
   */
  public function kumbiaCreateModel($modelName, $modelClass = 'ActiveRecord') {
    $file = "app/models/{$modelName}.php";
    $fs = new Filesystem();
    if (!$fs->exists($file)) {
      $this->say("<info>Creando Modelo</info>");
      //crear archivo
      $fs->touch($file);
      //escribir template
      $modelName = ucfirst($modelName);
      $this->taskWriteToFile($file)
         ->line("<?php")
         ->line("\tclass {$modelName} extends {$modelClass} {")
         ->line("\t\t")
         ->line("\t}")
         ->run();

      $this->say("<info>Modelo creado en {$file}</info>");
    } else {
      $this->say("<error>Modelo ya existía en {$file}</error>");
    }
  }

  /**
   * @description Crea un controlador scaffold para un modelo
   *
   * @param string $controllerName Nombre del controlador
   * @param string $modelName      Nombre del modelo
   */
  public function kumbiaCreateScaffold($controllerName, $modelName) {
    $file = "app/controllers/{$controllerName}_controller.php";
    $fs = new Filesystem();
    if (!$fs->exists($file)) {
      $this->say("<info>Creando Controlador</info>");
      //crear archivo
      $fs->touch($file);
      //escribir template
      $controllerName = ucfirst($controllerName);
      $this->taskWriteToFile($file)
         ->line("<?php")
         ->line("\tclass {$controllerName}Controller extends ScaffoldController {")
         ->line("\t\t" . 'public $model = "' . $modelName . '";')
         ->line("\t}")
         ->run();

      $this->say("<info>Controlador creado en {$file}</info>");
    } else {
      $this->say("<error>Controlador ya existía en {$file}</error>");
    }
    //verificar existencia del modelo, y en caso que no exista crearlo
    $modelFile = "app/models/{$modelName}.php";
    if (!$fs->exists($modelFile)) {
      $this->kumbiaCreateModel($modelName);
    }
  }
}
