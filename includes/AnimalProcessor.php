<?php
// RUTA: includes/AnimalProcessor.php (FINAL)

class AnimalProcessor {
    private $pdo;
    // La ruta es relativa al directorio 'includes/' (../../public/json/)
    private $rutaDestino = "../../public/json/"; 
    
    // Lista de provincias que corresponden a los nombres en la columna 'provincia_region' de la DB
    // Nota: 'Nacional' se maneja como archivo global con TODOS los datos.
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
        
        // Provincias + Nacional (Nacional será el archivo global con TODOS los registros)
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
                        a.id, 
                        a.nombre_cientifico, 
                        a.provincia_region, 
                        m.url_archivo 
                    FROM animales a
                    LEFT JOIN media_archivos m 
                        ON a.id = m.entidad_id 
                       AND m.tipo_entidad = 'animal' 
                       AND m.es_principal = 1
                    ORDER BY a.id ASC
                    LIMIT {$this->batchSize} OFFSET {$offset}";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            $loteAnimales = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // 2. Procesar el lote
            foreach ($loteAnimales as $animal) {
                $regionAnimal = $animal['provincia_region'];

                // 🔹 Solo los campos necesarios para el JSON
                $datosLimpios = [
                    "id"         => (int)$animal['id'],
                    "nombre"     => $animal['nombre_cientifico'], // nombre = nombre_cientifico
                    "imagen_url" => $animal['url_archivo']
                ];

                // --- LÓGICA DE ASIGNACIÓN ---

                // 1) Siempre se agrega al bucket 'Nacional' (TODOS los datos sin excepción)
                if (isset($datosProvinciales['Nacional'])) {
                    $datosProvinciales['Nacional'][] = $datosLimpios;
                }

                // 2) Manejo de provincias:
                //    - Si es 'Nacional' -> se copia a TODAS las provincias.
                //    - Si es una provincia específica -> solo a esa provincia.
                $destinos = [];
                if ($regionAnimal === 'Nacional') {
                    $destinos = $this->provincias; // Va a las 7 provincias
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

        // 4. Guardar los archivos JSON finales (provincias + Nacional)
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
        
        // Provincias + Nacional
        $provinciasYNacional = array_merge($this->provincias, ['Nacional']);
        
        foreach ($provinciasYNacional as $provinciaActual) {
            $lista = $datosProvinciales[$provinciaActual] ?? [];
            
            $jsonString = json_encode($lista, $opcionesJson);
            
            // LÓGICA DE NORMALIZACIÓN: Minúsculas, sin tildes, SIN ESPACIOS.
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
