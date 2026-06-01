import dao.ProductoDAO;
import modelo.Producto;

public class Main {

    public static void main(String[] args) {

        ProductoDAO dao = new ProductoDAO();

        // MAQUILLAJE
        System.out.println("--- Insertando MAQUILLAJE ---");
        dao.insertar(new Producto("Paleta de sombras", 90000, 20));
        dao.insertar(new Producto("Gloss Sheglam", 10000, 30));
        dao.insertar(new Producto("Base Nars", 200000, 15));
        dao.insertar(new Producto("Brochas", 30000, 25));
        dao.insertar(new Producto("Encrespador", 5000, 40));
        dao.insertar(new Producto("Contorno", 80000, 18));
        dao.insertar(new Producto("Pestañina", 50000, 22));
        dao.insertar(new Producto("Lapices para labios", 5000, 35));
        dao.insertar(new Producto("Delineador", 12000, 28));

        // CABELLO
        System.out.println("--- Insertando CABELLO ---");
        dao.insertar(new Producto("Shampo y Condicionador", 40000, 25));
        dao.insertar(new Producto("Protector térmico", 25000, 20));
        dao.insertar(new Producto("Mascarilla para cabello", 32000, 18));
        dao.insertar(new Producto("Masajeador de cuero cabelludo", 15000, 30));
        dao.insertar(new Producto("Jabón para cabello", 8000, 40));
        dao.insertar(new Producto("Enruladores", 15000, 25));
        dao.insertar(new Producto("Crema skala", 30000, 22));
        dao.insertar(new Producto("Aceite para cabello", 25000, 28));
        dao.insertar(new Producto("Aceite de coco", 40000, 20));

        // PIEL
        System.out.println("--- Insertando PIEL ---");
        dao.insertar(new Producto("Vaseline", 10000, 50));
        dao.insertar(new Producto("Protector solar", 30000, 25));
        dao.insertar(new Producto("Mascarilla para los labios", 30000, 20));
        dao.insertar(new Producto("Mascarilla para la cara", 10000, 35));
        dao.insertar(new Producto("Hidratante Aloe", 20000, 30));
        dao.insertar(new Producto("Crema Ponds", 25000, 28));
        dao.insertar(new Producto("Crema Nivea", 15000, 40));
        dao.insertar(new Producto("Crema CeraVe", 40000, 18));
        dao.insertar(new Producto("Agua micelar", 15000, 32));

        // ACCESORIOS
        System.out.println("--- Insertando ACCESORIOS ---");
        dao.insertar(new Producto("Moño", 5000, 50));
        dao.insertar(new Producto("Pinzas", 6000, 45));
        dao.insertar(new Producto("Gorro de dormir", 40000, 22));
        dao.insertar(new Producto("Aretes", 5000, 60));
        dao.insertar(new Producto("Caiman de cabello", 15000, 35));
        dao.insertar(new Producto("Pestañas postizas", 12000, 40));
        dao.insertar(new Producto("Uñas postizas", 10000, 38));
        dao.insertar(new Producto("Collar", 30000, 20));
        dao.insertar(new Producto("Antifaz para dormir", 20000, 25));

        // LISTAR todos los productos
        System.out.println("\n--- LISTADO DE TODOS LOS PRODUCTOS ---");
        dao.listar().forEach(p ->
            System.out.println(p.getNombre() + " - $" + p.getPrecio())
        );

        // ACTUALIZAR
        dao.actualizar(new Producto(1, "Labial Matte", 25000, 15));

        // ELIMINAR
        dao.eliminar(2);
    }
}