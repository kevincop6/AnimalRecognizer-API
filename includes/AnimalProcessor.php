<?php
// RUTA: includes/AnimalProcessor.php (FINAL: SOLO METADATOS Y ENLACES)

class AnimalProcessor {
    private $pdo;
    private $rutaDestino = "../../public/json/";
    private $provincias = [
        'San Jose', 'Alajuela', 'Cartago', 'Heredia', 'Guanacaste', 'Puntarenas', 'Limón'
    ];
    private $batchSize = 5000; 

    public function __construct($pdo) {
        $this->pdo = $pdo;
        if (!file_exists($this->rutaDestino)) {
            mkdir($this->rutaDestino, 0777, true);
        }
    }

    /**
     * Procesa la base de datos por lotes y genera los archivos JSON de metadatos y enlaces.
     * Tarea optimizada al 100% para evitar carga de CPU/Memoria.
     * @return array Resumen de la operación.
     */
    public function generateAndSaveProvinceJSONs() {
        // Ejecución ilimitada, se recomienda ejecutar por CLI
        set_time_limit(0); 

        $totalAnimales = $this->pdo->query("SELECT COUNT(id) FROM animales")->fetchColumn();
        $totalLotes = ceil($totalAnimales / $this->batchSize);
        
        $datosProvinciales = [];
        foreach ($this->provincias as $prov) {
            $datosProvinciales[$prov] = [];
        }

        $resultadoOperacion = ["lotes_procesados" => 0, "animales_procesados" => 0];

        // Bucle principal para el procesamiento por lotes
        for ($lote = 0; $lote < $totalLotes; $lote++) {
            $offset = $lote * $this->batchSize;

            // 1. Consulta SQL: Reintroducimos el JOIN para obtener el ENLACE (URL)
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
                
                // Formatear la descripción
                $descripcionTexto = $animal['descripcion'];
                if (substr(trim($descripcionTexto), 0, 1) === '{') {
                     $decoded_desc = json_decode($descripcionTexto, true);
                     $descripcionTexto = $decoded_desc['descripcion']['texto'] ?? $descripcionTexto; 
                }

                // Estructura final con el enlace de la imagen
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
                    "imagen_url" => $animal['url_archivo'] // <-- GUARDAMOS SOLO EL ENLACE
                ];

                // 3. Lógica de asignación (Nacional en todas las provincias)
                $provinciasParaGuardar = ($regionAnimal === 'Nacional') ? $this->provincias : [$regionAnimal];

                foreach ($provinciasParaGuardar as $provincia) {
                    if (isset($datosProvinciales[$provincia])) {
                        $datosProvinciales[$provincia][] = $datosLimpios;
                    }
                }
                $resultadoOperacion["animales_procesados"]++;
            }
            
            unset($loteAnimales); // Liberar memoria
            $resultadoOperacion["lotes_procesados"]++;
        }

        // 4. Guardar los archivos JSON finales
        $resultadosEscritura = $this->writeJSONFiles($datosProvinciales);
        
        return array_merge($resultadoOperacion, ["archivos_generados" => $resultadosEscritura]);
    }

    /**
     * Función auxiliar para escribir los archivos JSON.
     */
    private function writeJSONFiles($datosProvinciales) {
        $resultados = [];
        $opcionesJson = JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES;

        foreach ($this->provincias as $provinciaActual) {
            $lista = $datosProvinciales[$provinciaActual] ?? [];
            
            $jsonString = json_encode($lista, $opcionesJson);
            
            $nombreArchivo = str_replace([' ', 'ó', 'é', 'á', 'í', 'ú', 'ñ'], ['', 'o', 'e', 'a', 'i', 'u', 'n'], $provinciaActual);
            $rutaArchivo = $this->rutaDestino . strtolower($nombreArchivo) . ".json";
            
            if (file_put_contents($rutaArchivo, $jsonString)) {
                $resultados[] = "Generado: " . strtolower($nombreArchivo) . ".json (Animales: " . count($lista) . ")";
            } else {
                $resultados[] = "Error al guardar: " . strtolower($nombreArchivo) . ".json";
            }
        }
        return $resultados;
    }
}
?>