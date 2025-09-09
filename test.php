<?xml version="1.0" encoding="UTF-8"?>
<project xmlns="http://maven.apache.org/POM/4.0.0"
         xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:schemaLocation="http://maven.apache.org/POM/4.0.0 http://maven.apache.org/xsd/maven-4.0.0.xsd">
    <modelVersion>4.0.0</modelVersion>
    
    <groupId>com.example</groupId>
    <artifactId>pcomm-cursor</artifactId>
    <version>1.0</version>
    
    <properties>
        <maven.compiler.source>8</maven.compiler.source>
        <maven.compiler.target>8</maven.compiler.target>
    </properties>
    
    <dependencies>
        <dependency>
            <groupId>com.ibm</groupId>
            <artifactId>pcspapi</artifactId>
            <version>1.0</version>
            <scope>system</scope>
            <systemPath>${basedir}/lib/pcspapi.jar</systemPath>
        </dependency>
    </dependencies>
</project>

------


import com.ibm.eNetwork.ECL.*;

public class CursorReader {
    public static void main(String[] args) {
        try {
            ECLSession session = new ECLSession();
            session.SetConnectionByName("A"); // or specify your .ws file path
            
            ECLScreenDesc screen = new ECLScreenDesc();
            screen.SetSessionHandle(session.GetSessionHandle());
            
            int row = screen.GetCursorRow();
            int col = screen.GetCursorCol();
            
            System.out.println("Line: " + row + ", Column: " + col);
            
            session.UnsetConnection();
        } catch (Exception e) {
            System.err.println("Error: " + e.getMessage());
        }
    }
}