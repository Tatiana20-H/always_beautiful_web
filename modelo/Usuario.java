package modelo;

public class Usuario {
    private int id;
    private String nombre;
    private String correo;
    private String contrasena;
    private String rol;

    public Usuario(int id, String nombre, String correo, String contrasena, String rol) {
        this.id = id;
        this.nombre = nombre;
        this.correo = correo;
        this.contrasena = contrasena;
        this.rol = rol;
    }

    public Usuario(int id, String nombre, String correo, String rol) {
        this(id, nombre, correo, null, rol);
    }

    public Usuario(String nombre, String correo, String contrasena, String rol) {
        this(0, nombre, correo, contrasena, rol);
    }

    public int getId() { return id; }
    public String getNombre() { return nombre; }
    public String getCorreo() { return correo; }
    public String getContrasena() { return contrasena; }
    public String getRol() { return rol; }

    public void setId(int id) { this.id = id; }
    public void setNombre(String nombre) { this.nombre = nombre; }
    public void setCorreo(String correo) { this.correo = correo; }
    public void setContrasena(String contrasena) { this.contrasena = contrasena; }
    public void setRol(String rol) { this.rol = rol; }
}