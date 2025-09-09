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
        <dependency>
            <groupId>com.ibm</groupId>
            <artifactId>pcsapi</artifactId>
            <version>1.0</version>
            <scope>system</scope>
            <systemPath>${basedir}/lib/pcsapi.jar</systemPath>
        </dependency>
        <dependency>
            <groupId>com.ibm</groupId>
            <artifactId>base</artifactId>
            <version>1.0</version>
            <scope>system</scope>
            <systemPath>${basedir}/lib/base.jar</systemPath>
        </dependency>
    </dependencies>
</project>