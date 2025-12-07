<?php
// RUTA: includes/AnimalProcessor.php (FINAL)

class AnimalProcessor {
    private $pdo;
    // La ruta es relativa al directorio 'includes/' (../../public/json/)
    private $rutaDestino = "../../public/json/"; 
    
    // Lista de provincias que corresponden a los nombres en la columna 'provincia_region' de la DB
    // Nota: 'Nacional' es un caso especial que se maneja en la lógica.
    private $provincias = [
        'San Jose', 'Alajuela', 'Cartago', 'Heredia', 'Guanacaste', 'Puntarenas', 'Limón'
    ];
    
    private $batchSize = 5000; 

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
        // La ruta de destino debe ser relativa al script que llama a esta clase (generar_archivos_json.php), 
        // por lo que la ruta relativa aquí es correcta para la ejecución.
        $rutaAbsoluta = dirname(dirname(dirname(__FILE__))) . '/public/json/';
        if (!file_exists($rutaAbsoluta)) {
             mkdir($rutaAbsoluta, 0700, true);
        }
    }

    /**
     * Procesa la base de datos por lotes y genera los archivos JSON.
     * @return array Resumen de la operación.
     */
    public function generateAndSaveProvinceJSONs() {
        set_time_limit(0); 

        $totalAnimales = $this->pdo->query("SELECT COUNT(id) FROM animales")->fetchColumn();
        $totalLotes = ceil($totalAnimales / $this->batchSize);
        
        // Incluimos 'Nacional' y el resto de las provincias en los datos a ensamblar
        $provinciasYNacional = array_merge($this->provincias, ['Nacional']);
        
        $datosProvinciales = [];
        foreach ($provinciasYNacional as $prov) {
            $datosProvinciales[$prov] = [];
        }

        $resultadoOperacion = ["lotes_procesados" => 0, "animales_procesados" => 0];

        // Bucle principal para el procesamiento por lotes
        for ($lote = 0; $lote < $totalLotes; $lote++) {
            $offset = $lote * $this->batchSize;

            // Consulta SQL con LEFT JOIN para obtener la imagen principal
            $sql = "SELECT 
                        a.id, a.nombre_comun, a.nombre_cientifico, a.descripcion, a.pais_origen, 
                        a.provincia_region, a.taxonomia, m.url_archivo 
                    FROM animales a
                    LEFT JOIN media_archivos m 
                        ON a.id = m.entidad_id AND m.tipo_entidad = 'animal' AND m.es_principal = 1
                    ORDER BY a.id ASC
                    LIMIT {$this->batchSize} OFFSET {$offset}";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            $loteAnimales = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // 2. Procesar el lote
            foreach ($loteAnimales as $animal) {
                $regionAnimal = $animal['provincia_region'];
                
                // Formatear la descripción (extraer texto si está en JSON)
                $descripcionTexto = $animal['descripcion'];
                if (substr(trim($descripcionTexto), 0, 1) === '{') {
                     $decoded_desc = json_decode($descripcionTexto, true);
                     $descripcionTexto = $decoded_desc['descripcion']['texto'] ?? $descripcionTexto; 
                 }

                // Estructura de datos final
                $datosLimpios = [
                    "id" => $animal['id'],
                    "nombre" => $animal['nombre_cientifico'],
                    "nombre_comun" => $animal['nombre_comun'],
                    "descripcion" => $descripcionTexto,
                    "ubicacion" => [
                        "pais" => $animal['pais_origen'],
                        "provincia_origen" => $animal['provincia_region']
                    ],
                    "taxonomia" => json_decode($animal['taxonomia'] ?? '{}'),
                    "imagen_url" => $animal['url_archivo']
                ];

                // 3. Lógica de asignación (Si es Nacional, va a todas las provincias)
                // Usamos $provinciasYNacional para asegurar que 'Nacional' también se guarda en su propio archivo
                $destinos = [];
                if ($regionAnimal === 'Nacional') {
                    $destinos = $this->provincias; // Va a las 7 provincias
                    $destinos[] = 'Nacional'; // Y a su propio archivo Nacional.json
                } else {
                    $destinos[] = $regionAnimal;
                }

                foreach ($destinos as $provincia) {
                    if (isset($datosProvinciales[$provincia])) {
                        $datosProvinciales[$provincia][] = $datosLimpios;
                    }
                }
                $resultadoOperacion["animales_procesados"]++;
            }
            
            unset($loteAnimales);
            $resultadoOperacion["lotes_procesados"]++;
        }

        // 4. Guardar los archivos JSON finales
        $resultadosEscritura = $this->writeJSONFiles($datosProvinciales);
        
        return array_merge($resultadoOperacion, ["archivos_generados" => $resultadosEscritura]);
    }

    /**
     * Función auxiliar para escribir los archivos JSON, aplicando la normalización del nombre.
     */
    private function writeJSONFiles($datosProvinciales) {
        $resultados = [];
        // Opciones de JSON: UNESCAPED_UNICODE (para tildes), PRETTY_PRINT (formato legible), UNESCAPED_SLASHES
        $opcionesJson = JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES;
        
        // Incluimos Nacional en la escritura para generar su propio archivo
        $provinciasYNacional = array_merge($this->provincias, ['Nacional']);
        
        foreach ($provinciasYNacional as $provinciaActual) {
            $lista = $datosProvinciales[$provinciaActual] ?? [];
            
            $jsonString = json_encode($lista, $opcionesJson);
            
            // 🚩 LÓGICA DE NORMALIZACIÓN CRÍTICA: Minúsculas, sin tildes, SIN ESPACIOS.
            $nombreArchivoLimpio = strtolower($provinciaActual);
            $nombreArchivoLimpio = str_replace(
                [' ', 'ó', 'é', 'á', 'í', 'ú', 'ñ'], 
                ['', 'o', 'e', 'a', 'i', 'u', 'n'], 
                $nombreArchivoLimpio
            );
            
            $rutaArchivo = $this->rutaDestino . $nombreArchivoLimpio . ".json";
            
            if (file_put_contents($rutaArchivo, $jsonString)) {
                $resultados[] = "Generado: " . $nombreArchivoLimpio . ".json (Animales: " . count($lista) . ")";
            } else {
                $resultados[] = "Error al guardar: " . $nombreArchivoLimpio . ".json. Revise permisos de escritura.";
            }
        }
        return $resultados;
    }
}
?>