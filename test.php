import com.sun.jna.Library;
import com.sun.jna.Native;
import com.sun.jna.platform.win32.WinDef.HWND;
import com.sun.jna.platform.win32.User32;
import com.sun.jna.platform.win32.WinDef.POINT;

public class CursorPosition {
    public static void main(String[] args) {
        try {
            HWND pcommWindow = User32.INSTANCE.FindWindow(null, "IBM Personal Communications");
            if (pcommWindow == null) {
                System.err.println("PCOMM window not found");
                return;
            }
            
            POINT cursor = new POINT();
            User32.INSTANCE.GetCursorPos(cursor);
            User32.INSTANCE.ScreenToClient(pcommWindow, cursor);
            
            // Approximate character position (adjust based on font size)
            int charWidth = 8;  // pixels per character
            int charHeight = 16; // pixels per character
            int headerOffset = 30; // window header offset
            
            int col = (cursor.x / charWidth) + 1;
            int row = ((cursor.y - headerOffset) / charHeight) + 1;
            
            System.out.println("Line: " + row + ", Column: " + col);
            
        } catch (Exception e) {
            System.err.println("Error: " + e.getMessage());
        }
    }
}

--------

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
    
    <dependencies>
        <dependency>
            <groupId>net.java.dev.jna</groupId>
            <artifactId>jna-platform</artifactId>
            <version>5.13.0</version>
        </dependency>
    </dependencies>
    
    <build>
        <plugins>
            <plugin>
                <groupId>org.codehaus.mojo</groupId>
                <artifactId>exec-maven-plugin</artifactId>
                <version>3.1.0</version>
                <configuration>
                    <mainClass>CursorPosition</mainClass>
                </configuration>
            </plugin>
        </plugins>
    </build>
</project>