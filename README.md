# Alerta de Cambio de Precio en Magento 2

## Resumen

El módulo `ProductAlertChangePrice` mejora la funcionalidad predeterminada de Alerta de Producto en Adobe Commerce (Magento) 2.4.6. Permite a los administradores filtrar alertas de precios según SKUs específicos de productos y gestionar eficientemente grandes volúmenes de suscripciones de clientes mediante el procesamiento por lotes.

## Funcionalidades

- **Filtrado por SKU**: Los administradores pueden especificar una lista de SKUs en la configuración del módulo. Las alertas de precios solo se enviarán para los productos cuyos SKUs estén incluidos en esta lista.
- **Procesamiento por Lotes**: El módulo procesa las suscripciones de clientes en lotes para evitar la sobrecarga de memoria, lo que lo hace adecuado para tiendas con un gran número de suscriptores.

## Instalación

### Vía Composer

1. Agrega el repositorio a tu `composer.json`:

    ```bash
    composer config repositories.product-alert-change-price vcs https://github.com/attilai/magento2-product-alert-change-price
    ```

2. Requiere el módulo a través de Composer:

    ```bash
    composer require attilai/magento2-product-alert-change-price
    ```

3. Ejecuta el comando de actualización de Magento:

    ```bash
    php bin/magento setup:upgrade
    ```

4. Limpia la caché de Magento:

    ```bash
    php bin/magento cache:clean
    ```

### Instalación Manual

1. Descarga el módulo desde el repositorio de GitHub.
2. Extrae los archivos en `app/code/OlehVas/ProductAlertChangePrice`.
3. Ejecuta el comando de actualización de Magento:

    ```bash
    php bin/magento setup:upgrade
    ```

4. Limpia la caché de Magento:

    ```bash
    php bin/magento cache:clean
    ```

## Configuración

### Configuración en el Administrador

1. **Lista de SKU para Alerta de Producto**:
    - Ruta: `Stores > Configuration > Catalog > Product Alerts`
    - Descripción: Ingresa los SKUs (separados por comas) para los cuales deseas enviar alertas de precios.

2. **Tamaño del Lote**:
    - Ruta: `Stores > Configuration > Catalog > Product Alerts`
    - Descripción: Ingresa el número de clientes que se procesarán en cada lote. El valor predeterminado es `10000`.



### Ejemplo de Configuración

- **Lista de SKU para Alerta de Producto**: `ABC123, XYZ789, PQR456`
- **Tamaño del Lote**: `5000`

## Uso

Este módulo opera automáticamente cuando se guardan los productos. Si el SKU del producto guardado coincide con alguno de los SKUs configurados en el panel de administración, el módulo cargará los IDs de los clientes en lotes y les notificará sobre el cambio de precio.

### Flujo del Proceso

1. **Filtrado por SKU**: El módulo verifica si el SKU del producto está en la lista de SKUs configurados.
2. **Carga por Lotes**: Los IDs de los clientes se cargan en lotes para evitar la sobrecarga de memoria.
3. **Notificación**: Los clientes son notificados sobre el cambio de precio en función de los IDs cargados.

## Compatibilidad

- **Versión de Adobe Commerce (Magento)**: 2.4.6
- **Versión de PHP**: 8.1 - 8.2

## Contribuciones

Si deseas contribuir a este proyecto, no dudes en enviar una solicitud de extracción o abrir un problema en GitHub.

## Autor

- **Oleh Talalaiev**
- **Email**: [olegattila@gmail.com](mailto:olegattila@gmail.com)
- **GitHub**: [attilai](https://github.com/attilai)

#### PD:
Si hay que hacer un módulo completamente independiente, podemos tomar como base Magento_ProductAlert y limpiar la funcionalidad de Stocks. No lo hice para no perder tiempo en copiar y pegar.
