<?php
/*
Generador de sitemap.xml para sitios con archivos estáticos
*/

//Ubicación del directorio a analizar
define('PATH_DIR', realpath(__DIR__ . '\\'));

//Ubicación y nombre del sitemap resultante
define('SITEMAP_OUTPUT', PATH_DIR.'\sitemap.xml');

//Nombre de dominio para el proyecto
define('SITE_DOMAIN', 'https://neuralpin.com');

//Definimos si queremos o no URLs amigables
define('IS_FRIENDLY', true);

//Archivos o carpetas a evitar
define('IGNORED_LIST', [
    PATH_DIR.'\404.html',
    PATH_DIR.'\.git',
    PATH_DIR.'\_cms',
    PATH_DIR.'\theme',
    PATH_DIR.'\php',
    PATH_DIR.'\html',
]);

//Extensiones de los archivos a añadir al sitemap
define('ALLOWED_EXT', ['html', 'htm']);

/* Listar todos los archivos de un directorio con PHP */
function list_files( string $path ): array{
    
    $data = [];

    foreach( new DirectoryIterator($path) as $f ){
        
        if( 
            //Evitamos los archivos '.', '..' que son accesos directos
            $f->getBasename() != '.' && $f->getBasename() != '..'

            //Quitamos archivos/carpetas a ignorar
            && !in_array( $f->getPathname(), IGNORED_LIST )
        ){

            //En caso de que se trate de un directorío usamos recursividad
            if( $f->isDir() ){

                //Si el directorio estaba vacío no lo añadimos
                $newlist = list_files( $path.'\\'.$f->getBasename() );
                if( $newlist ) $data[$f->getBasename()] = $newlist;

            //En caso de solo ser un archivo lo añadimos a la lista
            }else{
                //Antes de añadirlo validamos que sea un tipo de archivo valido
                if( in_array( $f->getExtension(), ALLOWED_EXT ) )
                    $data[$f->getBasename()] = $f->getBasename();
            }

        }
    }

    return $data;
}

//Función para Generar <url>
function generate_link( string $link ): string{
    global $url;
    return preg_replace('/\[url\]/i', trim(SITE_DOMAIN.'/'.$link, '/'), $url[0]);
}

//Función para generar listado de <url>
function parse_list( array $data, string $parent = '' ): array{
    static $links = [];
    foreach( $data as $k => $i ){
        if( is_string($i) ){
            if( IS_FRIENDLY ){
                $i = pathinfo($i)['filename'];
                if( $i == 'index' ) $i = '';
            }
            $links[] = generate_link("{$parent}{$i}");
        }else parse_list( $i, "{$k}/" );
    }
    return $links;
}

//Obtenemos listado de paginas a añadir al sitemap
$list = list_files(PATH_DIR);

//Obtenemos la plantilla xml para el sitemap
$xmltemplate = file_get_contents( __DIR__ .'\\template.xml' );

//obtenemos el elemento xml para las <ulr>
preg_match('/<url>(.*)<\/url>/is',$xmltemplate,$url);

//Generamos listado de elementos <url>
$urllist = parse_list($list);

//Remplazamos listado en la plantilla XML
$xmltemplate = preg_replace('/<url>(.*)<\/url>/is', implode('',$urllist), $xmltemplate);

//Generamos sitemap.xml en la ruta especificada
$result = file_put_contents(SITEMAP_OUTPUT, $xmltemplate);

//Mostramos resultado
if( $result ) echo 'Sitemap generado correctamente en: ', SITEMAP_OUTPUT;
else 'Ocurrió un error al generar Sitemap';
