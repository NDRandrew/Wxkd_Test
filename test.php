<!-- pom.xml -->
<?xml version="1.0" encoding="UTF-8"?>
<project xmlns="http://maven.apache.org/POM/4.0.0"
         xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:schemaLocation="http://maven.apache.org/POM/4.0.0 
         http://maven.apache.org/xsd/maven-4.0.0.xsd">
    <modelVersion>4.0.0</modelVersion>
    
    <groupId>com.example</groupId>
    <artifactId>cursor-position</artifactId>
    <version>1.0.0</version>
    
    <properties>
        <maven.compiler.source>8</maven.compiler.source>
        <maven.compiler.target>8</maven.compiler.target>
        <project.build.sourceEncoding>UTF-8</project.build.sourceEncoding>
    </properties>
    
    <build>
        <plugins>
            <plugin>
                <groupId>org.codehaus.mojo</groupId>
                <artifactId>exec-maven-plugin</artifactId>
                <version>3.1.0</version>
                <configuration>
                    <mainClass>CursorPosition</mainClass>
                    <options>
                        <option>-Djava.library.path=${project.basedir}/lib</option>
                    </options>
                </configuration>
            </plugin>
        </plugins>
    </build>
</project>


-----------


public class CursorPosition {
    static {
        System.loadLibrary("pcshll32");
    }
    
    public static void main(String[] args) {
        int[] pos = new int[2];
        int result = hllapi(13, "", 0, pos); // Query cursor position
        
        if (result == 0) {
            int row = (pos[0] / 80) + 1;
            int col = (pos[0] % 80) + 1;
            System.out.println("Line: " + row + ", Column: " + col);
        } else {
            System.err.println("HLLAPI Error: " + result);
        }
    }
    
    private static native int hllapi(int func, String data, int len, int[] pos);
}