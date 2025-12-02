<div align="center">
  <img src="https://raw.githubusercontent.com/kevincop6/AnimalRecognizer/refs/heads/main/app/src/main/ic_launcher-playstore.png" alt="AnimalRecognizer Logo" width="150"/>
  <h1>AnimalRecognizer-API</h1>
  <p>
    <strong>El backend y núcleo de datos para la identificación de fauna silvestre en la Península de Osa, Costa Rica.</strong>
  </p>
</div>

---

## 📖 Sobre el Proyecto

**AnimalRecognizer-API** es el componente servidor que funciona en conjunto con la aplicación móvil **AnimalRecognizer**. Este proyecto nace en **Puerto Jiménez, Costa Rica**, con el objetivo de conectar la tecnología con la inmensa biodiversidad de la Península de Osa.

Mientras que la aplicación móvil realiza la detección de especies mediante **Inteligencia Artificial offline (Edge AI)**, esta API actúa como el cerebro administrativo en la nube, permitiendo las funciones de **Ciencia Ciudadana** y comunidad social.

### 🔗 Integración con la App
Este repositorio funciona exclusivamente como complemento de la aplicación móvil Android.
Puedes acceder al repositorio de la aplicación cliente aquí:

👉 **[Repositorio Oficial de la App (Android)](https://github.com/kevincop6/AnimalRecognizer)**

---

## ✨ Funciones del API en el Ecosistema

Aunque la identificación visual ocurre en el dispositivo del usuario, **AnimalRecognizer-API** es vital para gestionar la persistencia de datos y la colaboración científica. Sus funciones principales son:

### 1. Gestión de la "Red Social de Naturaleza"
Administra la plataforma donde los exploradores comparten sus hallazgos.
* **Usuarios:** Gestión de perfiles, autenticación y roles (aficionados vs. investigadores).
* **Aportes (Avistamientos):** Recepción y almacenamiento de fotografías y datos de avistamientos subidos por los usuarios para generar bases de datos reales de la fauna local.

### 2. Estructura de Datos Biológicos
Mantiene la integridad de la información científica que consume y nutre la aplicación.
* **Catálogo Central:** Administra la base de datos de animales con campos **JSON** para taxonomías complejas (Reino, Clase, Orden) y descripciones detalladas.
* **Distribución:** Almacena datos geoespaciales sobre dónde se encuentran las especies.

---

## 🗃️ Arquitectura de Datos

Este API sirve de interfaz para una base de datos relacional (**MySQL / MariaDB**) optimizada para soportar información biológica y social:

* **`animales`:** Tabla central con información taxonómica y estado de conservación.
* **`usuarios` & `photo_profile`:** Sistema de comunidad y avatares.
* **`aportes`:** Tabla pivote que conecta a los usuarios con las especies, creando el historial histórico de avistamientos para futuros análisis de población y migración.

---

<div align="center">
  <p><em>Desarrollado con ❤️ desde el corazón de la biodiversidad en Costa Rica.</em></p>
</div>
